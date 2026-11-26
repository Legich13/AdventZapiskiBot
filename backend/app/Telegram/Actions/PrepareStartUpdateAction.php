<?php

declare(strict_types=1);

namespace App\Telegram\Actions;

use App\Calendar\Actions\ClaimCalendarsAction;
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
        private WidgetRepository $messages,
    ) {}

    /** @return list<int>|null Список на момент получения /start. null означает отклонённый update. */
    public function execute(StartUpdateDTO $update): ?array
    {
        return DB::transaction(function () use ($update): ?array {
            if (! $this->updates->register($update->updateId, $update->viewerId)) {
                return $this->updates->originalCalendars($update->updateId, $update->viewerId);
            }
            if (! RateLimiter::attempt('telegram-start-user:'.$update->viewerId, 6, static fn (): bool => true, 60)) {
                return null;
            }
            $ids = $this->claim->execute($update->viewerId, $update->username);
            $this->messages->markBotReachable($update->viewerId);
            $this->updates->recordCalendars($update->updateId, $ids);

            return $ids;
        }, 3);
    }
}
