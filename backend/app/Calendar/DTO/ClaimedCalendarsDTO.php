<?php

declare(strict_types=1);

namespace App\Calendar\DTO;

final readonly class ClaimedCalendarsDTO
{
    /**
     * @param  list<int>  $received
     * @param  list<int>  $newlyClaimed
     */
    public function __construct(public array $received, public array $newlyClaimed) {}
}
