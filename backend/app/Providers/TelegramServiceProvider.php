<?php

declare(strict_types=1);

namespace App\Providers;

use App\Telegram\Contracts\MorningDeliveryRepository;
use App\Telegram\Contracts\WidgetRepository;
use App\Telegram\Infrastructure\Persistence\EloquentWidgetRepository;
use App\Telegram\Infrastructure\Persistence\PostgresMorningDeliveryRepository;
use Illuminate\Support\ServiceProvider;

final class TelegramServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->bind(WidgetRepository::class, EloquentWidgetRepository::class);
        $this->app->bind(MorningDeliveryRepository::class, PostgresMorningDeliveryRepository::class);
    }
}
