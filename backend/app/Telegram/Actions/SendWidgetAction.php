<?php

declare(strict_types=1);

namespace App\Telegram\Actions;

use App\Telegram\Contracts\WidgetRepository;
use App\Telegram\Infrastructure\NutgramGateway;
use App\Telegram\Services\WidgetRenderer;

final readonly class SendWidgetAction
{
    public function __construct(private WidgetRepository $messages, private WidgetRenderer $renderer, private NutgramGateway $gateway) {}

    public function execute(int $viewerId, int $calendarId, ?string $giftDate = null): int
    {
        $widget = $this->renderer->render($viewerId, $calendarId, $giftDate);
        $telegramId = $this->gateway->sendWidget($viewerId, $widget->html, $widget->caption);

        return $this->messages->recordMessage($viewerId, $calendarId, $giftDate, $telegramId, $widget);
    }
}
