<?php

declare(strict_types=1);

namespace App\Telegram\DTO;

final readonly class WidgetStateDTO
{
    /** @param array<int,int> $days Московский день месяца и его состояние. */
    public function __construct(public string $month, public array $days, public int $total) {}
}
