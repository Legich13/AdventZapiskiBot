<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability, array $arguments): ?bool {
            if (! app()->isLocal() && $user->hasRole('local_preview')) {
                return false;
            }
            if ($user->hasRole('super_admin')) {
                return true;
            }

            $model = $arguments[0] ?? null;
            if ($model === Role::class || $model instanceof Role) {
                return false;
            }

            return null;
        });
    }
}
