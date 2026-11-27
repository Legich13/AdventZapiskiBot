<?php

declare(strict_types=1);

namespace App\Telegram\DTO;

final readonly class StartReplyDTO
{
    /** @param list<int> $calendarIds Календари в этой открытке. */
    public function __construct(public BotMessageDTO $message, public array $calendarIds) {}
}
