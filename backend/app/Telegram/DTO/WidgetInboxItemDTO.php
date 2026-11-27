<?php

declare(strict_types=1);

namespace App\Telegram\DTO;

/** Сообщение в истории личного чата, включая ранее отправленные виджеты. */
final readonly class WidgetInboxItemDTO
{
    public function __construct(
        public int $id,
        public ?string $giftDate,
        public string $text,
        public ?string $button,
        public ?string $appPath,
        public ?string $photo,
    ) {}
}
