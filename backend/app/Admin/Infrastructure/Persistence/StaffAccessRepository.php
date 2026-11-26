<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Persistence;

use App\Models\User;
use DomainException;
use Spatie\Permission\Models\Role;

final class StaffAccessRepository
{
    public function replaceStaffRole(string $email, string $roleName): void
    {
        $staff = User::where('email', $email)->lockForUpdate()->first();
        if ($staff === null) {
            throw new DomainException('Сотрудник с указанной почтой не найден.');
        }
        if ($staff->hasRole('local_preview')) {
            throw new DomainException('Учётной записью предпросмотра управляет локальная мастерская.');
        }
        $role = Role::where('guard_name', 'web')->where('name', $roleName)->lockForUpdate()->first();
        if ($role === null) {
            throw new DomainException('Сначала создайте роль в разделе «Роли» панели Filament.');
        }
        $staff->syncRoles([$role]);
    }
}
