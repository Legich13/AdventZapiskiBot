<?php

declare(strict_types=1);

namespace App\Calendar\Services;

use App\Calendar\Contracts\CalendarRepository;
use App\Calendar\DTO\CalendarDTO;

final readonly class CalendarPublicationService
{
    public function __construct(private CalendarRepository $repository) {}

    public function ensureMonthIsAvailable(CalendarDTO $calendar, ?string $address): void
    {
        $recipientId = $calendar->recipientId ?? ($calendar->isSelf ? $calendar->authorId : $this->repository->idByUsername($address ?? ''));
        if ($recipientId === null) {
            return;
        }
        $user = $this->repository->lockIdentity($recipientId);
        $reservedForSelf = $calendar->isSelf && $calendar->publishedAt === null && $user->username !== null
            && $this->repository->awaitingMonth($user->username, $calendar->month);
        abort_if($reservedForSelf || $this->repository->monthTaken($calendar->id, $calendar->month, $recipientId), 409,
            'Для получателя уже опубликован календарь на этот месяц.');
    }
}
