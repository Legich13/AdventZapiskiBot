<?php

declare(strict_types=1);

namespace App\Telegram\DTO;

/** Пункт ленты: основной календарь либо карточка одной даты. */
final readonly class WidgetInboxItemDTO
{
    public function __construct(public int $id, public ?string $giftDate) {}
}
