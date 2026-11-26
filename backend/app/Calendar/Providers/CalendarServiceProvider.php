<?php

declare(strict_types=1);

namespace App\Calendar\Providers;

use App\Calendar\Contracts\CalendarRepository;
use App\Calendar\Infrastructure\Persistence\PostgresCalendarRepository;
use Illuminate\Support\ServiceProvider;
use Override;

final class CalendarServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->bind(CalendarRepository::class, PostgresCalendarRepository::class);
    }
}
