<?php

declare(strict_types=1);

namespace App\Admin\DTO;

final readonly class MorningDeliveryRowDTO
{
    public function __construct(public string $giftDate, public int $recipientId, public string $status) {}
}
