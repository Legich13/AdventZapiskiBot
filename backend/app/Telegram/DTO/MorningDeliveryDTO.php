<?php

declare(strict_types=1);

namespace App\Telegram\DTO;

use Carbon\CarbonImmutable;

final readonly class MorningDeliveryDTO
{
    public function __construct(public int $recipientId, public int $calendarId, public string $giftDate, public CarbonImmutable $availableAt) {}
}
