<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Telegram\Actions\DeliverMorningGiftAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Jobs\DatabaseJob;

final class SendMorningGift implements ShouldQueue
{
    use Queueable;

    public int $tries = 0;

    public int $backoff = 5;

    public function __construct(public int $deliveryId) {}

    public function displayName(): string
    {
        return self::class.':'.$this->deliveryId;
    }

    public function handle(DeliverMorningGiftAction $reminders): void
    {
        $hasWorker = $this->job instanceof DatabaseJob;
        $delay = $reminders->execute($this->deliveryId, $hasWorker);
        if ($hasWorker && $delay !== null) {
            $this->release($delay);
        }
    }
}
