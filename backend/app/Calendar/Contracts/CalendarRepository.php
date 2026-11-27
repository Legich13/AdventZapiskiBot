<?php

declare(strict_types=1);

namespace App\Calendar\Contracts;

use App\Calendar\DTO\CalendarDTO;
use App\Calendar\DTO\CalendarInputDTO;
use App\Calendar\DTO\OpenedGiftDTO;
use App\Calendar\DTO\TelegramIdentityDTO;

interface CalendarRepository
{
    public function identity(int $id): ?TelegramIdentityDTO;

    public function registerIdentity(TelegramIdentityDTO $identity): void;

    public function lockAddress(?string $username): void;

    public function lockIdentity(int $id): TelegramIdentityDTO;

    public function idByUsername(string $username): ?int;

    public function hasAuthored(int $authorId): bool;

    /** @return list<CalendarDTO> */
    public function authored(int $authorId): array;

    /** @return list<CalendarDTO> */
    public function received(int $recipientId): array;

    /** @return array<string,bool> Первое открытие каждой выбранной даты. */
    public function openedDates(int $calendarId): array;

    public function calendar(int $id): ?CalendarDTO;

    public function lockCalendar(int $id): ?CalendarDTO;

    /** @return list<CalendarDTO> */
    public function awaiting(string $username): array;

    /** @return list<int> */
    public function receivedIds(int $recipientId): array;

    /** @return array<string,string> */
    public function notes(int $id): array;

    public function saveGiftDates(int $authorId, ?int $id, CalendarInputDTO $input): int;

    public function publishDraft(int $id, ?int $selfRecipientId): void;

    public function claim(int $id, int $recipientId): void;

    public function claimAssignedToId(int $recipientId): void;

    public function restoreConflictedDraft(int $id): void;

    public function monthTaken(int $calendarId, string $month, int $recipientId): bool;

    public function awaitingMonth(string $username, string $month): bool;

    public function openDay(int $calendarId, string $date): ?OpenedGiftDTO;
}
