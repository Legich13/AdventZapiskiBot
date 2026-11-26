<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure;

use App\Telegram\Services\WidgetRenderer;
use SergiX44\Nutgram\Nutgram;

class NutgramGateway
{
    public function simulated(): bool
    {
        return app()->environment('local', 'testing');
    }

    public function sendWidget(int $recipient, string $html, string $caption): ?int
    {
        if ($this->simulated()) {
            return null;
        }

        $message = app(Nutgram::class)->sendWidget(html: $html, chat_id: $recipient, caption: $caption, allow_queries: true);

        return $message->message_id ?? throw new \RuntimeException('Telegram не подтвердил отправку виджета.');
    }

    public function welcome(int $recipient): void
    {
        if (! $this->simulated()) {
            $widget = app(WidgetRenderer::class)->renderWelcome(config()->string('services.telegram.mini_app_url'));
            app(Nutgram::class)->sendWidget(html: $widget->html, chat_id: $recipient, caption: $widget->caption, allow_queries: false);
        }
    }

    public function answer(string $query, string $result): void
    {
        if (! $this->simulated()) {
            app(Nutgram::class)->answerWidgetQuery(widget_query_id: $query, data: $result);
        }
    }
}
