<?php

declare(strict_types=1);

namespace App\Calendar\Actions;

use App\Calendar\Contracts\CalendarRepository;
use App\Calendar\DTO\TelegramIdentityDTO;
use Illuminate\Support\Facades\DB;

final readonly class ClaimCalendarsAction
{
    public function __construct(private CalendarRepository $repository) {}

    /** @return list<int> */
    public function execute(int $telegramId, ?string $username): array
    {
        $identity = TelegramIdentityDTO::fromTelegram($telegramId, $username);

        return DB::transaction(function () use ($identity): array {
            $this->repository->registerIdentity($identity);
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

            return $this->repository->receivedIds($identity->id);
        }, 3);
    }
}
