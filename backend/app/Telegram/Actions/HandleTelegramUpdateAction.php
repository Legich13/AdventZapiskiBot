<?php

declare(strict_types=1);

namespace App\Telegram\Actions;

use App\Telegram\Contracts\WidgetRepository;
use App\Telegram\DTO\StartUpdateDTO;
use App\Telegram\DTO\WidgetQueryDTO;
use App\Telegram\Infrastructure\NutgramGateway;
use App\Telegram\Infrastructure\Persistence\PostgresStartUpdateRepository;
use Closure;
use SergiX44\Nutgram\Telegram\Exceptions\TelegramException;

final readonly class HandleTelegramUpdateAction
{
    public function __construct(
        private PrepareStartUpdateAction $prepare,
        private PostgresStartUpdateRepository $updates,
        private SendWidgetAction $send,
        private AnswerWidgetAction $answer,
        private WidgetRepository $messages,
        private NutgramGateway $gateway,
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
            $this->start($update);

            return null;
        } catch (TelegramException $failure) {
            if ($failure->getCode() !== 429) {
                throw $failure;
            }
            $retry = $failure->getParameter('retry_after');

            return is_int($retry) ? max(1, $retry) : 5;
        } finally {
            $this->updates->unlockDelivery($update->updateId);
        }
    }

    private function start(StartUpdateDTO $update): void
    {
        $ids = $this->prepare->execute($update);
        if ($ids === null) {
            return;
        }
        $this->sendOnce($update->updateId, 0, fn () => $this->gateway->welcome($update->viewerId));
        foreach ($ids as $index => $id) {
            $this->sendOnce($update->updateId, $index + 1, fn () => $this->send->execute($update->viewerId, $id));
        }
    }

    /** @param Closure(): mixed $send */
    private function sendOnce(int $updateId, int $position, Closure $send): void
    {
        if ($this->updates->reserveMessage($updateId, $position)) {
            try {
                $send();
            } catch (TelegramException $failure) {
                if ($failure->getCode() === 429) {
                    $this->updates->releaseMessage($updateId, $position);
                }
                throw $failure;
            }
        }
    }
}
