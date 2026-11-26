<?php

declare(strict_types=1);

namespace App\Calendar\DTO;

final readonly class CalendarWriteResultDTO
{
    public function __construct(public int $id, public bool $created = false) {}
}
