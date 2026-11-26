<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TelegramUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<TelegramUser> */
final class TelegramUserFactory extends Factory
{
    #[Override]
    protected $model = TelegramUser::class;

    /** @return array<string,mixed> */
    #[Override]
    public function definition(): array
    {
        return ['id' => fake()->unique()->numberBetween(100_000, 2_000_000_000), 'username' => null];
    }
}
