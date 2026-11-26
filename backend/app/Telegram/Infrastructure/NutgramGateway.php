<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure;

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\WebApp\WebAppInfo;

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
            $keyboard = InlineKeyboardMarkup::make()->addRow(InlineKeyboardButton::make(
                'Создать календарь', web_app: WebAppInfo::make(url('/app')),
            ));
            app(Nutgram::class)->sendMessage('Соберите маленькие сюрпризы для себя или близкого человека ✨', chat_id: $recipient, reply_markup: $keyboard);
        }
    }

    public function answer(string $query, string $result): void
    {
        if (! $this->simulated()) {
            app(Nutgram::class)->answerWidgetQuery(widget_query_id: $query, data: $result);
        }
    }
}
