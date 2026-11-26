<?php

declare(strict_types=1);

use App\Calendar\Providers\CalendarServiceProvider;
use App\Providers\ApiDocumentationServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\TelegramServiceProvider;

return [AppServiceProvider::class, CalendarServiceProvider::class, TelegramServiceProvider::class, AdminPanelProvider::class, ApiDocumentationServiceProvider::class];
