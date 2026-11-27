<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Persistence;

use App\Jobs\SendMorningGift;
use App\Models\MorningDelivery;
use App\Models\TelegramUser;
use App\Telegram\Contracts\MorningDeliveryRepository;
use App\Telegram\DTO\MorningDeliveryDTO;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** Repository для PostgreSQL. Model: App\Models\MorningDelivery. */
final class PostgresMorningDeliveryRepository implements MorningDeliveryRepository
{
    /** {@inheritDoc} */
    public function recover(string $today, CarbonImmutable $staleBefore, ?int $viewerId): void
    {
        $scope = MorningDelivery::query()->when($viewerId !== null, fn ($q) => $q->where('recipient_id', $viewerId));
        (clone $scope)->where('status', 'sending')->where('updated_at', '<', $staleBefore)->update(['status' => 'unknown']);
        (clone $scope)->where('status', 'pending')->where('gift_date', '<', $today)->update(['status' => 'expired']);
        // Payload очереди хранится как TEXT и может содержать экранированные нулевые байты.
        // Новые задания несут ID в displayName, старые — в сериализованном публичном deliveryId.
        $liveJobs = DB::table(config()->string('queue.connections.database.table'))
            ->whereRaw('strpos(payload, ?) > 0', ['"commandName":'.json_encode(SendMorningGift::class, JSON_THROW_ON_ERROR)])
            ->selectRaw(<<<'SQL'
                COALESCE(
                    substring(payload from '"displayName"\s*:\s*"[^"]+:([0-9]+)"'),
                    substring(payload from 's:10:\\"deliveryId\\";i:([0-9]+);'),
                    '0'
                )::bigint
                SQL);
        (clone $scope)->where('status', 'pending')->whereNotIn('id', $liveJobs)->where('queued_at', '<', $staleBefore)
            ->where('available_at', '<=', now())->update(['queued_at' => null]);
    }

    /** {@inheritDoc} */
    public function reserveEligible(string $date, CarbonImmutable $receivedBy, ?int $viewerId): void
    {
        $candidates = DB::table('gift_days as g')->join('calendars as c', 'c.id', '=', 'g.calendar_id')
            ->join('telegram_users as u', 'u.id', '=', 'c.recipient_id')
            ->where('g.date', $date)->whereNotNull('c.published_at')->whereNull('u.bot_blocked_at')
            ->where('c.claimed_at', '<=', $receivedBy->utc())
            ->when($viewerId !== null, fn (Builder $q) => $q->where('u.id', $viewerId))
            ->whereNotExists($this->waitingCovers($date)->whereColumn('calendar_id', 'c.id'))
            ->whereNotExists(fn (Builder $q) => $q->from('morning_deliveries as d')->whereColumn('d.recipient_id', 'u.id')->where('d.gift_date', $date))
            ->select('c.id', 'c.recipient_id', 'g.date')->selectRaw('?::timestamp,?::timestamp,?::timestamp', [now(), now(), now()]);
        DB::table('morning_deliveries')->insertOrIgnoreUsing(
            ['calendar_id', 'recipient_id', 'gift_date', 'available_at', 'created_at', 'updated_at'],
            $candidates,
        );
    }

    /** {@inheritDoc} */
    public function dueIds(string $date, ?int $viewerId): iterable
    {
        foreach (MorningDelivery::query()->where('gift_date', $date)->where('status', 'pending')
            ->whereNull('queued_at')->where('available_at', '<=', now())
            ->when($viewerId !== null, fn ($q) => $q->where('recipient_id', $viewerId))
            ->select('id')->lazyById(250) as $delivery) {
            yield $delivery->id;
        }
    }

    /** {@inheritDoc} */
    public function reserveQueueSlot(int $id): bool
    {
        return MorningDelivery::query()->whereKey($id)->where('status', 'pending')->whereNull('queued_at')->update(['queued_at' => now()]) === 1;
    }

    /** {@inheritDoc} */
    public function pending(int $id): ?MorningDeliveryDTO
    {
        $row = MorningDelivery::query()->where('status', 'pending')->find($id);

        return $row === null ? null : new MorningDeliveryDTO($row->recipient_id, $row->calendar_id, $row->gift_date, $row->available_at);
    }

    public function waitsForCover(MorningDeliveryDTO $delivery): bool
    {
        return $this->waitingCovers($delivery->giftDate)->where('calendar_id', $delivery->calendarId)->exists();
    }

    private function waitingCovers(string $date): Builder
    {
        return DB::table('telegram_cover_waits')->where('gift_date', $date);
    }

    /** {@inheritDoc} */
    public function expire(int $id): void
    {
        MorningDelivery::query()->whereKey($id)->where('status', 'pending')->update(['status' => 'expired']);
    }

    /** {@inheritDoc} */
    public function beginSending(int $id): bool
    {
        return MorningDelivery::query()->whereKey($id)->where('status', 'pending')->where('available_at', '<=', now())->update(['status' => 'sending']) === 1;
    }

    /** {@inheritDoc} */
    public function defer(int $id, CarbonImmutable $next, bool $hasWorker, bool $sending): void
    {
        MorningDelivery::query()->whereKey($id)->whereIn('status', $sending ? ['sending', 'unknown'] : ['pending'])
            ->update(['status' => 'pending', 'available_at' => $next, 'queued_at' => $hasWorker ? now() : null]);
    }

    /** {@inheritDoc} */
    public function confirmSent(int $id, int $messageId): void
    {
        $updated = MorningDelivery::query()->whereKey($id)->whereIn('status', ['sending', 'unknown'])
            ->update(['status' => 'sent', 'published_message_id' => $messageId, 'queued_at' => null]);
        if ($updated !== 1) {
            throw new \LogicException('Ожидалось подтверждение активной отправки.');
        }
    }

    /** {@inheritDoc} */
    public function finishWithoutMessage(int $id, string $status): void
    {
        MorningDelivery::query()->whereKey($id)->whereIn('status', ['sending', 'unknown'])->update(['status' => $status, 'queued_at' => null]);
    }

    /** {@inheritDoc} */
    public function markBotBlocked(int $viewerId): void
    {
        TelegramUser::query()->whereKey($viewerId)->update(['bot_blocked_at' => now()]);
    }
}
