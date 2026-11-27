<?php

declare(strict_types=1);

namespace App\Telegram\Actions;

use App\Calendar\Actions\ClaimCalendarsAction;
use App\Calendar\Contracts\CalendarRepository;
use App\Support\GiftClock;
use App\Telegram\Contracts\WidgetRepository;
use App\Telegram\DTO\StartUpdateDTO;
use App\Telegram\Infrastructure\Persistence\PostgresStartUpdateRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

final readonly class PrepareStartUpdateAction
{
    public function __construct(
        private PostgresStartUpdateRepository $updates,
        private ClaimCalendarsAction $claim,
        private CalendarRepository $calendars,
        private WidgetRepository $messages,
    ) {}

    public function execute(StartUpdateDTO $update): void
    {
        DB::transaction(function () use ($update): void {
            if (! $this->updates->register($update->updateId, $update->viewerId)) {
                return;
            }
            if (! RateLimiter::attempt('telegram-start-user:'.$update->viewerId, 6, static fn (): bool => true, 60)) {
                return;
            }
            $claimed = $this->claim->claimForStart($update->viewerId, $update->username);
            $this->messages->markBotReachable($update->viewerId);
            $create = $claimed->received === [] && ! $this->calendars->hasAuthored($update->viewerId);
            $this->updates->recordCalendars($update->updateId, $claimed->newlyClaimed, $create, GiftClock::now('Europe/Moscow')->toDateString());
        }, 3);
    }
}
