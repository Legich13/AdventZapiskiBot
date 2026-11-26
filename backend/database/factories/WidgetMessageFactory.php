<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Calendar;
use App\Models\TelegramUser;
use App\Models\WidgetMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WidgetMessage> */
final class WidgetMessageFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'recipient_id' => TelegramUser::factory(),
            'calendar_id' => static fn (array $attributes): int => Calendar::factory()->create([
                'recipient_id' => $attributes['recipient_id'], 'month' => now('Europe/Moscow')->startOfMonth()->toDateString(),
                'published_at' => now(), 'claimed_at' => now(),
            ])->id,
            'gift_date' => now('Europe/Moscow')->toDateString(),
            'telegram_message_id' => null, 'html' => '<p>Подарок!</p>', 'caption' => 'Доброе утро',
        ];
    }
}
