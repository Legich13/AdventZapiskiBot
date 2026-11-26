<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Calendar;
use App\Models\TelegramUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<Calendar> */
final class CalendarFactory extends Factory
{
    #[Override]
    protected $model = Calendar::class;

    /** @return array<string,mixed> */
    #[Override]
    public function definition(): array
    {
        return ['author_id' => TelegramUser::factory(), 'month' => '2026-12-01', 'is_self' => true];
    }
}
