<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Calendar;
use App\Models\GiftDay;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<GiftDay> */
final class GiftDayFactory extends Factory
{
    #[Override]
    protected $model = GiftDay::class;

    /** @return array<string,mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'calendar_id' => Calendar::factory(),
            'date' => static function (array $attributes): string {
                /** @var int $calendarId */
                $calendarId = $attributes['calendar_id'];

                return Calendar::findOrFail($calendarId)->month;
            },
            'text' => 'Пусть сегодня случится чудо!',
        ];
    }
}
