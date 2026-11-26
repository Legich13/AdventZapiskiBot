<?php

declare(strict_types=1);

namespace App\Telegram\DTO;

final readonly class WidgetTargetDTO
{
    public function __construct(public int $calendarId, public string $month, public ?string $giftDate) {}
}
