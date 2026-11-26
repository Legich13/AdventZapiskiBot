<?php

declare(strict_types=1);

namespace App\Telegram\Actions;

use App\Support\GiftClock;
use App\Telegram\Contracts\MorningDeliveryRepository;
use App\Telegram\Contracts\WidgetRepository;
use App\Telegram\Infrastructure\NutgramGateway;
use App\Telegram\Services\MorningRateLimiter;
use App\Telegram\Services\WidgetRenderer;
use Illuminate\Support\Facades\DB;
use SergiX44\Nutgram\Telegram\Exceptions\TelegramException;
use Throwable;

final readonly class DeliverMorningGiftAction
{
    public function __construct(
        private MorningDeliveryRepository $deliveries,
        private WidgetRenderer $renderer,
        private NutgramGateway $gateway,
        private WidgetRepository $messages,
        private MorningRateLimiter $limit,
    ) {}

    /** Для отложенной попытки возвращает число секунд до освобождения задания. */
    public function execute(int $id, bool $hasWorker): ?int
    {
        $delivery = $this->deliveries->pending($id);
        if ($delivery === null) {
            return null;
        }
        if ($delivery->giftDate !== GiftClock::now('Europe/Moscow')->toDateString()) {
            $this->deliveries->expire($id);

            return null;
        }
        if ($delivery->availableAt->isFuture()) {
            return max(1, (int) ceil(now()->diffInSeconds($delivery->availableAt)));
        }
        $widget = $this->renderer->render($delivery->recipientId, $delivery->calendarId, $delivery->giftDate);
        $wait = $this->limit->reserveSlot();
        if ($wait > 0) {
            $this->deliveries->defer($id, now()->toImmutable()->addSeconds($wait), $hasWorker, false);

            return $wait;
        }
        if (! $this->deliveries->beginSending($id)) {
            return null;
        }
        try {
            $telegramId = $this->gateway->sendWidget($delivery->recipientId, $widget->html, $widget->caption);
        } catch (TelegramException $failure) {
            if ($failure->getCode() === 429) {
                $retry = $failure->getParameter('retry_after');
                $wait = is_int($retry) ? max(1, $retry) : 5;
                $next = now()->toImmutable()->addSeconds($wait);
                try {
                    $this->limit->pauseUntil($next);
                } finally {
                    $this->deliveries->defer($id, $next, $hasWorker, true);
                }

                return $wait;
            }
            DB::transaction(function () use ($id, $delivery, $failure): void {
                if ($failure->getCode() === 403) {
                    $this->deliveries->markBotBlocked($delivery->recipientId);
                    $this->deliveries->finishWithoutMessage($id, 'blocked');
                } else {
                    $this->deliveries->finishWithoutMessage($id, 'failed');
                }
            });

            return null;
        } catch (Throwable $failure) {
            logger()->warning('morning_delivery_unknown', ['delivery_id' => $id, 'exception' => $failure::class]);
            $this->deliveries->finishWithoutMessage($id, 'unknown');

            return null;
        }
        DB::transaction(function () use ($id, $delivery, $widget, $telegramId): void {
            $message = $this->messages->recordMessage($delivery->recipientId, $delivery->calendarId, $delivery->giftDate, $telegramId, $widget);
            $this->deliveries->confirmSent($id, $message);
        });

        return null;
    }
}
