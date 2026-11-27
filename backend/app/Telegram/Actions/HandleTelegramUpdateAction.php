<?php

declare(strict_types=1);

namespace App\Telegram\Actions;

use App\Telegram\Contracts\WidgetRepository;
use App\Telegram\DTO\StartUpdateDTO;
use App\Telegram\DTO\WidgetQueryDTO;
use App\Telegram\Infrastructure\NutgramGateway;
use App\Telegram\Infrastructure\Persistence\PostgresStartUpdateRepository;
use App\Telegram\Infrastructure\PhotoPreparationException;
use Illuminate\Support\Facades\DB;
use SergiX44\Nutgram\Telegram\Exceptions\TelegramException;

final readonly class HandleTelegramUpdateAction
{
    public function __construct(
        private PrepareStartUpdateAction $prepare,
        private PostgresStartUpdateRepository $updates,
        private AnswerWidgetAction $answer,
        private WidgetRepository $messages,
        private NutgramGateway $gateway,
        private DispatchMorningRemindersAction $reminders,
    ) {}

    /** Возвращает retry-after, если Telegram нужно повторить доставку обновления. */
    public function execute(StartUpdateDTO|WidgetQueryDTO|null $update): ?int
    {
        if ($update === null) {
            return null;
        }
        if ($update instanceof WidgetQueryDTO) {
            $id = $update->telegramMessageId === null ? null : $this->messages->localIdForTelegramMessage($update->viewerId, $update->telegramMessageId);
            $this->gateway->answer($update->queryId, $this->answer->execute($update->viewerId, $id ?? 0, $update->command));

            return null;
        }
        if (! $this->updates->lockForDelivery($update->updateId)) {
            return 3;
        }
        try {
            $this->prepare->execute($update);
        } finally {
            $this->updates->unlockDelivery($update->updateId);
        }

        // Если вызывающий код ещё держит транзакцию, webhook должен допустить повтор.
        $retry = 3;
        DB::afterCommit(function () use ($update, &$retry): void {
            $retry = $this->deliverCommitted($update);
        });

        return $retry;
    }

    private function deliverCommitted(StartUpdateDTO $update): ?int
    {
        if (! $this->updates->lockForDelivery($update->updateId)) {
            return 3;
        }
        try {
            $retry = $this->updates->retryAfter($update->updateId, $update->viewerId);
            if ($retry > 0) {
                return $retry;
            }
            $reply = DB::transaction(fn () => $this->updates->reserveReply($update->updateId, $update->viewerId));
            if ($reply === null) {
                return null;
            }
            try {
                $id = $this->gateway->send($update->viewerId, $reply->message);
            } catch (PhotoPreparationException $failure) {
                DB::transaction(fn () => $this->updates->release($update->updateId));
                throw $failure;
            } catch (TelegramException $failure) {
                $code = $failure->getCode();
                if ($code < 400 || $code >= 500) {
                    return null;
                }
                if ($code !== 429) {
                    DB::transaction(fn () => $this->updates->reject($update->updateId, $update->viewerId, $reply->calendarIds, $code));

                    return null;
                }
                $seconds = $failure->getParameter('retry_after');
                $wait = is_int($seconds) ? max(1, $seconds) : 5;
                DB::transaction(fn () => $this->updates->release($update->updateId, $wait));

                return $wait;
            }
            DB::transaction(function () use ($id, $reply, $update): void {
                $this->messages->recordMessage($update->viewerId, $id, $reply->message);
                $this->updates->confirm($update->updateId, $reply->calendarIds);
            });
        } finally {
            $this->updates->unlockDelivery($update->updateId);
        }
        $this->reminders->execute($update->viewerId);

        return null;
    }
}
