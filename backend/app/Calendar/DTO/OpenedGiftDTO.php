<?php

declare(strict_types=1);

namespace App\Calendar\DTO;

final readonly class OpenedGiftDTO
{
    public function __construct(
        public string $text,
        public bool $first,
    ) {}
}
