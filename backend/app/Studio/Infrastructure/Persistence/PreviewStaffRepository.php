<?php

declare(strict_types=1);

namespace App\Studio\Infrastructure\Persistence;

use App\Models\TelegramUser;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

final class PreviewStaffRepository
{
    public function loginId(): int
    {
        $staff = User::firstOrCreate(['email' => 'preview@advent.invalid'], ['name' => 'Локальный просмотр', 'password' => Str::random(64)]);
        $staff->assignRole(Role::findOrCreate('local_preview', 'web'));

        return $staff->id;
    }

    public function profileExists(int $id): bool
    {
        return TelegramUser::whereKey($id)->exists();
    }
}
