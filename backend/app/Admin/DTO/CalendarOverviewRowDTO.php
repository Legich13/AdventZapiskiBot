<?php

declare(strict_types=1);

namespace App\Admin\DTO;

final readonly class CalendarOverviewRowDTO
{
    public function __construct(
        public string $month,
        public string $author,
        public string $address,
        public ?string $recipient,
        public ?string $publishedAt,
        public ?string $claimedAt,
        public int $openedCount,
        public ?string $lastOpened,
    ) {}
}
