<?php

declare(strict_types=1);

namespace App\Admin\DTO;

final readonly class TelegramPersonDTO
{
    public function __construct(public int $id, public ?string $username) {}
}
