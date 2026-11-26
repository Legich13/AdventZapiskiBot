<?php

declare(strict_types=1);

namespace App\Telegram\Actions;

use App\Jobs\SendMorningGift;
use App\Support\GiftClock;
use App\Telegram\Contracts\MorningDeliveryRepository;
use Illuminate\Support\Facades\DB;

final readonly class DispatchMorningRemindersAction
{
    public function __construct(private MorningDeliveryRepository $deliveries) {}

    public function execute(?int $onlyRecipient = null): int
    {
        $now = GiftClock::now('Europe/Moscow')->toImmutable();
        $date = $now->toDateString();
        $cutoff = $now->setTime(10, 0);
        $this->deliveries->recover($date, now()->toImmutable()->utc()->subMinutes(5), $onlyRecipient);
        if ($now->lt($cutoff)) {
            return 0;
        }
        $this->deliveries->reserveEligible($date, $now, $onlyRecipient);
        $count = 0;
        foreach ($this->deliveries->dueIds($date, $onlyRecipient) as $id) {
            $reserved = DB::transaction(function () use ($id): bool {
                if (! $this->deliveries->reserveQueueSlot($id)) {
                    return false;
                }
                SendMorningGift::dispatch($id)->afterCommit();

                return true;
            });
            if ($reserved) {
                $count++;
            }
        }

        return $count;
    }
}
