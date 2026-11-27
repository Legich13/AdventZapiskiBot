<?php

declare(strict_types=1);

namespace App\Telegram\DTO;

use Carbon\CarbonImmutable;

final readonly class BotMessageDTO
{
    public function __construct(
        public string $text,
        public string $button,
        public string $appPath,
        public ?string $photo = null,
        public ?int $calendarId = null,
        public ?string $giftDate = null,
    ) {}

    /** @param list<int> $newCalendars */
    public static function start(array $newCalendars, bool $create): self
    {
        if ($newCalendars === []) {
            return $create
                ? new self('Соберите календарь с записками для себя или близкого человека.', 'Создать календарь', '/app?create=1')
                : new self('Ваши календари и подарки ждут в приложении.', 'Мои календари', '/app');
        }
        if (count($newCalendars) === 1) {
            $id = $newCalendars[0];

            return new self('Для вас приготовили календарь с подарками.', 'Открыть календарь', '/app/received/'.$id, '/images/calendar-cover.png', $id);
        }

        return new self('Для вас приготовили несколько календарей с подарками.', 'Мои календари', '/app', '/images/calendar-cover.png');
    }

    public static function morning(int $calendarId, string $date, bool $opened): self
    {
        $giftDay = CarbonImmutable::parse($date);
        $giftDay->locale('ru');
        $label = $giftDay->isoFormat('D MMMM');

        return new self($opened ? "Ваш подарок за $label можно перечитать." : "$label · Для вас есть подарок ✨",
            $opened ? 'Посмотреть ещё раз' : 'К подарку', "/app/received/$calendarId/$date", calendarId: $calendarId, giftDate: $date);
    }
}
