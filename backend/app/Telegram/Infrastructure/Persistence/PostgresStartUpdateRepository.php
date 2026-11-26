<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;

final class PostgresStartUpdateRepository
{
    public function lockForDelivery(int $updateId): bool
    {
        return DB::connection()->scalar('select pg_try_advisory_lock(hashtextextended(?, 0))', ['advent:start:'.$updateId], false) === true;
    }

    public function unlockDelivery(int $updateId): void
    {
        DB::connection()->scalar('select pg_advisory_unlock(hashtextextended(?, 0))', ['advent:start:'.$updateId], false);
    }

    public function register(int $updateId, int $viewerId): bool
    {
        return DB::table('telegram_start_updates')->insertOrIgnore(['update_id' => $updateId, 'viewer_id' => $viewerId]) === 1;
    }

    /** @param list<int> $ids */
    public function recordCalendars(int $updateId, array $ids): void
    {
        DB::table('telegram_start_updates')->where('update_id', $updateId)->whereNull('calendar_ids')->update(['calendar_ids' => json_encode($ids, JSON_THROW_ON_ERROR)]);
    }

    /** @return list<int>|null */
    public function originalCalendars(int $updateId, int $viewerId): ?array
    {
        $json = DB::table('telegram_start_updates')->where('update_id', $updateId)->where('viewer_id', $viewerId)->value('calendar_ids');
        if (! is_string($json)) {
            return null;
        }
        /** @var list<int> $ids Encoded by recordCalendars(). */
        $ids = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        return $ids;
    }

    public function reserveMessage(int $updateId, int $position): bool
    {
        return DB::table('telegram_start_messages')->insertOrIgnore(['update_id' => $updateId, 'position' => $position]) === 1;
    }

    public function releaseMessage(int $updateId, int $position): void
    {
        DB::table('telegram_start_messages')->where('update_id', $updateId)->where('position', $position)->delete();
    }
}
