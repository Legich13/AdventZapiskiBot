<?php

declare(strict_types=1);

namespace App\Calendar\Infrastructure\Persistence;

use App\Calendar\Contracts\CalendarRepository;
use App\Calendar\DTO\CalendarDTO;
use App\Calendar\DTO\CalendarInputDTO;
use App\Calendar\DTO\OpenedGiftDTO;
use App\Calendar\DTO\TelegramIdentityDTO;
use App\Models\Calendar;
use App\Models\GiftDay;
use App\Models\TelegramUser;
use App\Support\GiftClock;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class PostgresCalendarRepository implements CalendarRepository
{
    public function identity(int $id): ?TelegramIdentityDTO
    {
        $user = TelegramUser::find($id);

        return $user === null ? null : new TelegramIdentityDTO($user->id, $user->username);
    }

    public function registerIdentity(TelegramIdentityDTO $identity): void
    {
        $this->lockAddress($identity->username);
        if ($identity->username !== null) {
            TelegramUser::where('username', $identity->username)->whereKeyNot($identity->id)->update(['username' => null]);
        }
        TelegramUser::upsert([['id' => $identity->id, 'username' => $identity->username]], ['id'], ['username']);
    }

    public function lockAddress(?string $username): void
    {
        if ($username !== null) {
            DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['advent:'.$username]);
        }
    }

    public function lockIdentity(int $id): TelegramIdentityDTO
    {
        $user = TelegramUser::query()->lockForUpdate()->findOrFail($id);

        return new TelegramIdentityDTO($user->id, $user->username);
    }

    public function idByUsername(string $username): ?int
    {
        return TelegramUser::where('username', $username)->first(['id'])?->id;
    }

    public function authored(int $authorId): array
    {
        return array_values(Calendar::where('author_id', $authorId)->latest()->latest('id')->get()->map($this->dto(...))->all());
    }

    public function calendar(int $id): ?CalendarDTO
    {
        $calendar = Calendar::find($id);

        return $calendar === null ? null : $this->dto($calendar);
    }

    public function lockCalendar(int $id): ?CalendarDTO
    {
        $calendar = Calendar::query()->lockForUpdate()->find($id);

        return $calendar === null ? null : $this->dto($calendar);
    }

    public function receivedIds(int $recipientId): array
    {
        return array_values(Calendar::where('recipient_id', $recipientId)->whereNotNull('published_at')->whereNotNull('claimed_at')->orderByDesc('month')
            ->get(['id'])->map(static fn (Calendar $calendar): int => $calendar->id)->all());
    }

    public function awaiting(string $username): array
    {
        return array_values(Calendar::where('recipient_username', $username)->whereNull('recipient_id')
            ->whereNotNull('published_at')->orderBy('id')->lockForUpdate()->get()->map($this->dto(...))->all());
    }

    public function notes(int $id): array
    {
        $notes = [];
        foreach (GiftDay::where('calendar_id', $id)->orderBy('date')->get(['date', 'text']) as $day) {
            $notes[$day->date] = $day->text;
        }

        return $notes;
    }

    public function saveGiftDates(int $authorId, ?int $id, CalendarInputDTO $input): int
    {
        $calendar = $id === null ? new Calendar(['author_id' => $authorId]) : Calendar::findOrFail($id);
        $calendar->fill(['is_self' => $input->self, 'month' => $input->month, 'recipient_username' => $input->username]);
        try {
            $calendar->save();
        } catch (UniqueConstraintViolationException $exception) {
            $this->rethrowMonthConflict($exception);
        }
        $calendar->days()->whereNotIn('date', array_keys($input->days))->delete();
        foreach ($input->days as $date => $text) {
            $calendar->days()->updateOrCreate(['date' => $date], ['text' => $text]);
        }

        return $calendar->id;
    }

    public function publishDraft(int $id, ?int $selfRecipientId): void
    {
        try {
            Calendar::whereKey($id)->update([
                'published_at' => GiftClock::now(), 'claim_conflict_at' => null, 'recipient_id' => $selfRecipientId,
                'claimed_at' => $selfRecipientId === null ? null : GiftClock::now(),
            ]);
        } catch (UniqueConstraintViolationException $exception) {
            $this->rethrowMonthConflict($exception);
        }
    }

    public function claim(int $id, int $recipientId): void
    {
        Calendar::whereKey($id)->update(['recipient_id' => $recipientId, 'claimed_at' => GiftClock::now()]);
    }

    public function claimAssignedToId(int $recipientId): void
    {
        Calendar::where('recipient_id', $recipientId)->whereNotNull('published_at')->whereNull('claimed_at')
            ->update(['claimed_at' => GiftClock::now()]);
    }

    public function restoreConflictedDraft(int $id): void
    {
        Calendar::whereKey($id)->update(['published_at' => null, 'claim_conflict_at' => GiftClock::now()]);
    }

    public function monthTaken(int $calendarId, string $month, int $recipientId): bool
    {
        return Calendar::where('recipient_id', $recipientId)->where('month', $month)
            ->whereNotNull('published_at')->whereKeyNot($calendarId)->exists();
    }

    public function awaitingMonth(string $username, string $month): bool
    {
        return Calendar::where('recipient_username', $username)->where('month', $month)
            ->whereNotNull('published_at')->whereNull('recipient_id')->exists();
    }

    public function openDay(int $calendarId, string $date): ?OpenedGiftDTO
    {
        $day = GiftDay::where('calendar_id', $calendarId)->where('date', $date)->lockForUpdate()->first();
        if ($day === null) {
            return null;
        }
        $first = $day->opened_at === null;
        if ($first) {
            $day->opened_at = GiftClock::now();
            $day->save();
        }

        return new OpenedGiftDTO($day->text, $first);
    }

    private function dto(Calendar $calendar): CalendarDTO
    {
        return new CalendarDTO(
            $calendar->id, $calendar->author_id, $calendar->recipient_id, $calendar->recipient_username,
            $calendar->is_self, $calendar->month, $calendar->published_at?->toImmutable(),
            $calendar->claimed_at?->toImmutable(), $calendar->claim_conflict_at?->toImmutable(),
        );
    }

    private function rethrowMonthConflict(UniqueConstraintViolationException $exception): never
    {
        if (str_contains($exception->getMessage(), 'calendars_claimed_month') || str_contains($exception->getMessage(), 'calendars_pending_month')) {
            throw new ConflictHttpException('Для получателя уже опубликован календарь на этот месяц.', $exception);
        }

        throw $exception;
    }
}
