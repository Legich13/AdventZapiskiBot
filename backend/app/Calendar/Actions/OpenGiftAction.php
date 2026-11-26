<?php

declare(strict_types=1);

namespace App\Calendar\Actions;

use App\Calendar\Contracts\CalendarRepository;
use App\Calendar\DTO\OpenedGiftDTO;
use App\Policies\CalendarPolicy;
use App\Support\GiftClock;
use Illuminate\Support\Facades\DB;

final readonly class OpenGiftAction
{
    public function __construct(private CalendarRepository $repository, private CalendarPolicy $policy) {}

    public function execute(int $viewerId, int $calendarId, string $date): OpenedGiftDTO
    {
        abort_unless(preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)
            && checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4)), 404);

        return DB::transaction(function () use ($viewerId, $calendarId, $date): OpenedGiftDTO {
            abort_unless($this->policy->receive($viewerId, $this->repository->calendar($calendarId)), 403);
            abort_if($date > GiftClock::now('Europe/Moscow')->toDateString(), 403, 'Подарок откроется в свою дату.');
            $gift = $this->repository->openDay($calendarId, $date);
            abort_if($gift === null, 404, 'На этот день подарка нет.');

            return $gift;
        }, 3);
    }
}
