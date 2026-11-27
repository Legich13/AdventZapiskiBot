<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure;

use App\Telegram\DTO\BotMessageDTO;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Internal\InputFile;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\WebApp\WebAppInfo;

class NutgramGateway
{
    public function simulated(): bool
    {
        return app()->environment('local', 'testing');
    }

    public function send(int $recipient, BotMessageDTO $content): ?int
    {
        if ($this->simulated()) {
            return null;
        }

        $url = rtrim(config()->string('app.url'), '/').$content->appPath;
        $keyboard = InlineKeyboardMarkup::make()->addRow(InlineKeyboardButton::make(
            $content->button, web_app: WebAppInfo::make($url),
        ));
        $bot = app(Nutgram::class);
        if ($content->photo === null) {
            $message = $bot->sendMessage($content->text, chat_id: $recipient, reply_markup: $keyboard);
        } else {
            $stream = @fopen(public_path(ltrim($content->photo, '/')), 'rb');
            if ($stream === false) {
                throw new PhotoPreparationException('Не удалось открыть обложку календаря.');
            }
            $message = $bot->sendPhoto(InputFile::make($stream, basename($content->photo)), chat_id: $recipient, caption: $content->text, reply_markup: $keyboard);
        }

        return $message->message_id ?? throw new \RuntimeException('Telegram не подтвердил отправку сообщения.');
    }

    public function answer(string $query, string $result): void
    {
        if (! $this->simulated()) {
            app(Nutgram::class)->answerWidgetQuery(widget_query_id: $query, data: $result);
        }
    }
}
