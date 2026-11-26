<?php

declare(strict_types=1);

namespace App\Telegram\DTO;

final readonly class StartUpdateDTO
{
    public function __construct(public int $updateId, public int $viewerId, public ?string $username) {}
}
