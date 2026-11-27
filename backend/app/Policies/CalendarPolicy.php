<?php

declare(strict_types=1);

namespace App\Policies;

use App\Calendar\Contracts\CalendarRepository;
use App\Calendar\DTO\CalendarDTO;
use App\Support\GiftClock;

final readonly class CalendarPolicy
{
    public function __construct(private CalendarRepository $repository) {}

    public function manage(int $actor, CalendarDTO $calendar): bool
    {
        return $calendar->authorId === $actor;
    }

    public function mayEdit(int $actor, int $calendarId): bool
    {
        $calendar = $this->repository->calendar($calendarId);
        abort_if($calendar === null, 404);

        return $this->manage($actor, $calendar);
    }

    public function receive(int $actor, ?CalendarDTO $calendar): bool
    {
        return $calendar !== null && $calendar->publishedAt !== null && $calendar->recipientId === $actor && $calendar->claimedAt !== null;
    }

    public function ensureAvailableDate(CalendarDTO $calendar, string $date): void
    {
        abort_unless(preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)
            && str_starts_with($date, substr($calendar->month, 0, 7).'-')
            && checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4)), 404);
        abort_if($date > GiftClock::now('Europe/Moscow')->toDateString(), 403, 'Подарок откроется в свою дату.');
    }
}
