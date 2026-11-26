<?php

declare(strict_types=1);

namespace App\Calendar\DTO;

final readonly class EditorPageDTO
{
    /**
     * @param  list<CalendarDTO>  $calendars
     * @param  array<string,string>  $notes  Тексты выбранного авторского календаря, по дате.
     */
    public function __construct(
        public ?TelegramIdentityDTO $viewer,
        public array $calendars,
        public ?CalendarDTO $calendar,
        public array $notes,
        public string $today,
    ) {}
}
