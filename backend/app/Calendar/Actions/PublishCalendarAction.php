<?php

declare(strict_types=1);

namespace App\Calendar\Actions;

use App\Calendar\Contracts\CalendarRepository;
use App\Calendar\DTO\CalendarWriteResultDTO;
use App\Calendar\Services\CalendarPublicationService;
use App\Policies\CalendarPolicy;
use App\Telegram\Actions\DispatchMorningRemindersAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class PublishCalendarAction
{
    public function __construct(
        private CalendarRepository $repository,
        private CalendarPublicationService $publication,
        private CalendarPolicy $policy,
        private DispatchMorningRemindersAction $reminders,
    ) {}

    public function execute(int $authorId, int $id): CalendarWriteResultDTO
    {
        return DB::transaction(function () use ($authorId, $id): CalendarWriteResultDTO {
            $calendar = $this->repository->lockCalendar($id);
            abort_if($calendar === null, 404);
            abort_unless($this->policy->manage($authorId, $calendar), 403);
            if ($calendar->publishedAt === null) {
                $this->repository->lockAddress($calendar->recipientUsername);
                $days = $this->repository->notes($id);
                if ($days === [] || in_array('', array_map(trim(...), $days), true)) {
                    throw ValidationException::withMessages(['days' => 'Добавьте хотя бы один день и заполните каждую выбранную записку.']);
                }
                $this->publication->ensureMonthIsAvailable($calendar, $calendar->recipientUsername);
                $this->repository->publishDraft($id, $calendar->isSelf ? $authorId : null);
                if ($calendar->isSelf) {
                    $this->reminders->execute($authorId);
                }
            }

            return new CalendarWriteResultDTO($id);
        }, 3);
    }
}
