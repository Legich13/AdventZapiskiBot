<?php

declare(strict_types=1);

namespace App\Calendar\Actions;

use App\Calendar\Contracts\CalendarRepository;
use App\Calendar\DTO\ClaimedCalendarsDTO;
use App\Calendar\DTO\TelegramIdentityDTO;
use Illuminate\Support\Facades\DB;

final readonly class ClaimCalendarsAction
{
    public function __construct(private CalendarRepository $repository) {}

    /** @return list<int> */
    public function execute(int $telegramId, ?string $username): array
    {
        return $this->claimForStart($telegramId, $username)->received;
    }

    public function claimForStart(int $telegramId, ?string $username): ClaimedCalendarsDTO
    {
        $identity = TelegramIdentityDTO::fromTelegram($telegramId, $username);

        return DB::transaction(function () use ($identity): ClaimedCalendarsDTO {
            $this->repository->registerIdentity($identity);
            $before = $this->repository->receivedIds($identity->id);
            $this->repository->claimAssignedToId($identity->id);
            if ($identity->username !== null) {
                foreach ($this->repository->awaiting($identity->username) as $calendar) {
                    if ($this->repository->monthTaken($calendar->id, $calendar->month, $identity->id)) {
                        $this->repository->restoreConflictedDraft($calendar->id);
                    } else {
                        $this->repository->claim($calendar->id, $identity->id);
                    }
                }
            }

            $received = $this->repository->receivedIds($identity->id);

            return new ClaimedCalendarsDTO($received, array_values(array_diff($received, $before)));
        }, 3);
    }
}
