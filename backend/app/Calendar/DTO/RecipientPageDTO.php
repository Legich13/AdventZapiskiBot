<?php

declare(strict_types=1);

namespace App\Calendar\DTO;

final readonly class RecipientPageDTO
{
    /** @param array<string,bool> $opened Только даты и состояние открытия. */
    public function __construct(
        public int $id,
        public string $month,
        public string $today,
        public array $opened,
        public ?string $selected,
        public int $refreshAfterMs,
    ) {}
}
