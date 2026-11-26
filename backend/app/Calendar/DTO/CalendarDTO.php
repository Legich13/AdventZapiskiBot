<?php

declare(strict_types=1);

namespace App\Calendar\DTO;

final readonly class CalendarDTO
{
    public function __construct(
        public int $id,
        public int $authorId,
        public ?int $recipientId,
        public ?string $recipientUsername,
        public bool $isSelf,
        public string $month,
        public ?\DateTimeImmutable $publishedAt,
        public ?\DateTimeImmutable $claimedAt,
        public ?\DateTimeImmutable $claimConflictAt,
    ) {}
}
