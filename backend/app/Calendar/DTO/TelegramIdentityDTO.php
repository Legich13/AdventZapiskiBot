<?php

declare(strict_types=1);

namespace App\Calendar\DTO;

final readonly class TelegramIdentityDTO
{
    public function __construct(
        public int $id,
        public ?string $username,
    ) {}

    public static function fromTelegram(int $id, ?string $username): self
    {
        return new self($id, $username === null ? null : strtolower($username));
    }
}
