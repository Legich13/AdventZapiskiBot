<?php

declare(strict_types=1);

namespace App\Calendar\DTO;

/** Данные редактора после проверки входа; дни индексируются их датами. */
final readonly class CalendarInputDTO
{
    /** @param array<string,string> $days */
    public function __construct(
        public string $month,
        public bool $self,
        public ?string $username,
        public array $days,
    ) {}

    public static function giftFits(string $text): bool
    {
        return strlen(json_encode(['text' => $text, 'first' => false], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) <= 1024;
    }
}
