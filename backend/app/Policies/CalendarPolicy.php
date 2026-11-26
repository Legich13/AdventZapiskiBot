<?php

declare(strict_types=1);

namespace App\Policies;

use App\Calendar\Contracts\CalendarRepository;
use App\Calendar\DTO\CalendarDTO;

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
        return $calendar !== null && $calendar->recipientId === $actor && $calendar->claimedAt !== null;
    }
}
