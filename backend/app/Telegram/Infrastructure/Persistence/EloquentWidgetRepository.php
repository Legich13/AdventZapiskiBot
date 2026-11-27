<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Persistence;

use App\Models\Calendar;
use App\Models\GiftDay;
use App\Models\TelegramUser;
use App\Models\WidgetMessage;
use App\Telegram\Contracts\WidgetRepository;
use App\Telegram\DTO\BotMessageDTO;
use App\Telegram\DTO\WidgetInboxItemDTO;
use App\Telegram\DTO\WidgetStateDTO;
use App\Telegram\DTO\WidgetTargetDTO;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/** Repository для PostgreSQL. Model: App\Models\WidgetMessage. */
final class EloquentWidgetRepository implements WidgetRepository
{
    /** {@inheritDoc} */
    public function stateForRecipient(int $viewerId, int $calendarId, string $today): ?WidgetStateDTO
    {
        $calendar = Calendar::query()->where('recipient_id', $viewerId)->whereNotNull('claimed_at')->find($calendarId, ['id', 'month']);
        if ($calendar === null) {
            return null;
        }
        $days = [];
        foreach (GiftDay::query()->where('calendar_id', $calendarId)->orderBy('date')->get(['date', 'opened_at']) as $gift) {
            $days[(int) substr($gift->date, -2)] = $gift->opened_at !== null ? 2 : ($gift->date <= $today ? 1 : 0);
        }

        return new WidgetStateDTO(substr($calendar->month, 0, 7), $days, CarbonImmutable::parse($calendar->month)->daysInMonth);
    }

    /** {@inheritDoc} */
    public function recordMessage(int $viewerId, ?int $telegramId, BotMessageDTO $message): int
    {
        return WidgetMessage::query()->create([
            'calendar_id' => $message->calendarId, 'recipient_id' => $viewerId, 'gift_date' => $message->giftDate,
            'telegram_message_id' => $telegramId, 'caption' => $message->text,
            'button_text' => $message->button, 'app_path' => $message->appPath, 'photo' => $message->photo,
        ])->id;
    }

    /** {@inheritDoc} */
    public function targetForRecipient(int $viewerId, int $id): ?WidgetTargetDTO
    {
        $message = WidgetMessage::query()->with('calendar:id,month')->whereNull('app_path')->where('recipient_id', $viewerId)->find($id, ['id', 'calendar_id', 'gift_date']);
        if ($message === null || $message->calendar_id === null || $message->calendar === null) {
            return null;
        }

        return new WidgetTargetDTO($message->calendar_id, substr($message->calendar->month, 0, 7), $message->gift_date);
    }

    /** {@inheritDoc} */
    public function localIdForTelegramMessage(int $viewerId, int $telegramId): ?int
    {
        return WidgetMessage::query()->where('recipient_id', $viewerId)->whereNull('app_path')->where('telegram_message_id', $telegramId)->first(['id'])?->id;
    }

    /** {@inheritDoc} */
    public function recentForViewer(int $viewerId): Collection
    {
        return WidgetMessage::query()->where('recipient_id', $viewerId)->latest('id')->limit(40)->get(['id', 'gift_date', 'caption', 'button_text', 'app_path', 'photo'])
            ->map(fn (WidgetMessage $message): WidgetInboxItemDTO => new WidgetInboxItemDTO($message->id, $message->gift_date, $message->caption, $message->button_text, $message->app_path, $message->photo));
    }

    /** {@inheritDoc} */
    public function previewForViewer(int $viewerId, int $messageId): ?string
    {
        return WidgetMessage::query()->where('recipient_id', $viewerId)->find($messageId, ['html'])?->html;
    }

    /** {@inheritDoc} */
    public function exists(int $messageId): bool
    {
        return WidgetMessage::query()->whereKey($messageId)->exists();
    }

    /** {@inheritDoc} */
    public function markBotReachable(int $viewerId): void
    {
        TelegramUser::query()->whereKey($viewerId)->update(['bot_blocked_at' => null]);
    }
}
