<?php

declare(strict_types=1);

namespace App\Calendar\Services;

use App\Calendar\Contracts\CalendarRepository;
use App\Calendar\DTO\CalendarDTO;
use App\Calendar\DTO\RecipientPageDTO;
use App\Policies\CalendarPolicy;
use App\Support\GiftClock;

final readonly class RecipientPageService
{
    public function __construct(private CalendarRepository $repository, private CalendarPolicy $policy) {}

    /** @return list<CalendarDTO> */
    public function calendars(int $viewerId): array
    {
        return $this->repository->received($viewerId);
    }

    public function page(int $viewerId, int $id, ?string $date): RecipientPageDTO
    {
        $calendar = $this->repository->calendar($id);
        abort_unless($this->policy->receive($viewerId, $calendar) && $calendar !== null, 403);
        $opened = $this->repository->openedDates($calendar->id);
        if ($date !== null) {
            $this->policy->ensureAvailableDate($calendar, $date);
            abort_unless(array_key_exists($date, $opened), 404);
        }

        $now = GiftClock::now('Europe/Moscow');
        $refreshAfterMs = (int) $now->diffInMilliseconds($now->copy()->addDay()->startOfDay());

        return new RecipientPageDTO($calendar->id, $calendar->month, $now->toDateString(), $opened, $date, $refreshAfterMs);
    }
}
