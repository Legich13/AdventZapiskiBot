<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Persistence;

use App\Telegram\DTO\BotMessageDTO;
use App\Telegram\DTO\StartReplyDTO;
use Carbon\CarbonImmutable;
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
    public function recordCalendars(int $updateId, array $ids, bool $create, string $today): void
    {
        $viewer = DB::table('telegram_start_updates')->where('update_id', $updateId)->value('viewer_id');
        /** @var list<int> $retryIds Только подтверждённый отказ разрешает новый человеческий запуск. */
        $retryIds = DB::table('telegram_cover_waits as w')->join('calendars as c', 'c.id', '=', 'w.calendar_id')
            ->where('c.recipient_id', $viewer)->where('w.retry_on_start', true)->orderBy('w.calendar_id')
            ->lock('for update of w')->pluck('w.calendar_id')->all();
        if ($ids !== []) {
            DB::table('telegram_cover_waits')->insertOrIgnore(array_map(
                static fn (int $id): array => ['calendar_id' => $id, 'gift_date' => $today], $ids,
            ));
        }
        if ($retryIds !== []) {
            DB::table('telegram_cover_waits')->whereIn('calendar_id', $retryIds)->update(['retry_on_start' => false]);
            $ids = array_values(array_unique([...$ids, ...$retryIds]));
        }
        DB::table('telegram_start_updates')->where('update_id', $updateId)->whereNull('calendar_ids')->update([
            'calendar_ids' => json_encode($ids, JSON_THROW_ON_ERROR),
            'create_calendar' => $create,
            'outcome' => 'pending',
        ]);
    }

    public function retryAfter(int $updateId, int $viewerId): int
    {
        $at = DB::table('telegram_start_updates')->where('update_id', $updateId)->where('viewer_id', $viewerId)->value('retry_at');

        return is_string($at) ? max(0, (int) ceil(now()->diffInSeconds(CarbonImmutable::parse($at, 'UTC')))) : 0;
    }

    /** Резервирует единственный ответ на команду до отправки в Telegram. */
    public function reserveReply(int $updateId, int $viewerId): ?StartReplyDTO
    {
        $row = DB::table('telegram_start_updates')->where('update_id', $updateId)->where('viewer_id', $viewerId)
            ->whereIn('outcome', ['pending', 'retry'])->lockForUpdate()->first(['calendar_ids', 'create_calendar']);
        if ($row === null || ! is_string($row->calendar_ids)) {
            return null;
        }
        /** @var list<int> $ids */
        $ids = json_decode($row->calendar_ids, true, flags: JSON_THROW_ON_ERROR);
        // Если после сетевого запроса процесс прервётся, новый воркер не отправит дубликат.
        $this->setOutcome($updateId, 'unknown');

        return new StartReplyDTO(BotMessageDTO::start($ids, (bool) $row->create_calendar), $ids);
    }

    public function release(int $updateId, int $retryAfter = 0): void
    {
        DB::table('telegram_start_updates')->where('update_id', $updateId)->update([
            'outcome' => $retryAfter > 0 ? 'retry' : 'pending',
            'telegram_error_code' => $retryAfter > 0 ? 429 : null,
            'retry_at' => $retryAfter > 0 ? now()->utc()->addSeconds($retryAfter)->ceilSecond() : null,
        ]);
    }

    /** @param list<int> $ids */
    public function confirm(int $updateId, array $ids): void
    {
        $this->setOutcome($updateId, 'sent');
        DB::table('telegram_cover_waits')->whereIn('calendar_id', $ids)->delete();
    }

    /** @param list<int> $ids */
    public function reject(int $updateId, int $viewerId, array $ids, int $code): void
    {
        $this->setOutcome($updateId, $code === 403 ? 'blocked' : 'failed', $code);
        DB::table('telegram_cover_waits')->whereIn('calendar_id', $ids)->update(['retry_on_start' => true]);
        if ($code === 403) {
            DB::table('telegram_users')->where('id', $viewerId)->update(['bot_blocked_at' => now()]);
        }
    }

    private function setOutcome(int $updateId, string $outcome, ?int $code = null): void
    {
        DB::table('telegram_start_updates')->where('update_id', $updateId)->update(['outcome' => $outcome, 'telegram_error_code' => $code, 'retry_at' => null]);
    }
}
