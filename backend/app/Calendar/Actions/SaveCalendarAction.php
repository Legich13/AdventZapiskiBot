<?php

declare(strict_types=1);

namespace App\Calendar\Actions;

use App\Calendar\Contracts\CalendarRepository;
use App\Calendar\DTO\CalendarDTO;
use App\Calendar\DTO\CalendarInputDTO;
use App\Calendar\DTO\CalendarWriteResultDTO;
use App\Calendar\Services\CalendarPublicationService;
use App\Policies\CalendarPolicy;
use App\Support\GiftClock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class SaveCalendarAction
{
    public function __construct(
        private CalendarRepository $repository,
        private CalendarPublicationService $publication,
        private CalendarPolicy $policy,
    ) {}

    public function execute(int $authorId, ?int $id, CalendarInputDTO $input): CalendarWriteResultDTO
    {
        return DB::transaction(function () use ($authorId, $id, $input): CalendarWriteResultDTO {
            $this->repository->lockAddress($input->username);
            if ($id !== null) {
                $current = $this->repository->lockCalendar($id);
                abort_if($current === null, 404);
                abort_unless($this->policy->manage($authorId, $current), 403);
                if ($current->publishedAt !== null) {
                    $this->checkPublishedChanges($current, $input);
                    $this->publication->ensureMonthIsAvailable($current, $input->username);
                }
            }

            return new CalendarWriteResultDTO($this->repository->saveGiftDates($authorId, $id, $input), $id === null);
        }, 3);
    }

    private function checkPublishedChanges(CalendarDTO $calendar, CalendarInputDTO $input): void
    {
        if ($input->month !== $calendar->month || $input->self !== $calendar->isSelf) {
            throw ValidationException::withMessages(['month' => 'После публикации месяц и способ адресации закреплены.']);
        }
        if ($calendar->recipientId !== null && $input->username !== $calendar->recipientUsername) {
            throw ValidationException::withMessages(['username' => 'Получатель закреплён по Telegram ID.']);
        }
        $old = $this->repository->notes($calendar->id);
        if (array_diff_key($old, $input->days) !== [] || array_diff_key($input->days, $old) !== []) {
            throw ValidationException::withMessages(['days' => 'После публикации выбранные даты закреплены.']);
        }
        $today = GiftClock::now('Europe/Moscow')->toDateString();
        foreach ($input->days as $date => $text) {
            if (trim($text) === '' || ($date <= $today && $text !== $old[$date])) {
                throw ValidationException::withMessages(['days' => 'Можно исправлять только непустые записки будущих дней.']);
            }
        }
    }
}
