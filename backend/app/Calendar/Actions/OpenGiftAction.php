<?php

declare(strict_types=1);

namespace App\Calendar\Actions;

use App\Calendar\Contracts\CalendarRepository;
use App\Calendar\DTO\OpenedGiftDTO;
use App\Policies\CalendarPolicy;
use Illuminate\Support\Facades\DB;

final readonly class OpenGiftAction
{
    public function __construct(private CalendarRepository $repository, private CalendarPolicy $policy) {}

    public function execute(int $viewerId, int $calendarId, string $date): OpenedGiftDTO
    {
        return DB::transaction(function () use ($viewerId, $calendarId, $date): OpenedGiftDTO {
            $calendar = $this->repository->calendar($calendarId);
            abort_unless($this->policy->receive($viewerId, $calendar) && $calendar !== null, 403);
            $this->policy->ensureAvailableDate($calendar, $date);
            $gift = $this->repository->openDay($calendarId, $date);
            abort_if($gift === null, 404, 'На этот день подарка нет.');

            return $gift;
        }, 3);
    }
}
