<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\Handbook;
use App\Filament\Pages\Overview;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;

final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel->default()->id('admin')->path('admin')->login()
            ->brandName('Декабрь чудес')->colors(['primary' => Color::Rose])
            ->pages([Overview::class, Handbook::class])->plugin(FilamentShieldPlugin::make())
            ->middleware(['web', DisableBladeIconComponents::class, DispatchServingFilamentEvent::class])
            ->authMiddleware([Authenticate::class], isPersistent: true);
    }
}
