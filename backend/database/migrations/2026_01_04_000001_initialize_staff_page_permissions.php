<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $pages = [
            Permission::findOrCreate('View:Overview', 'web'),
            Permission::findOrCreate('View:Handbook', 'web'),
        ];
        foreach (['support', 'local_preview', 'super_admin'] as $name) {
            Role::findOrCreate($name, 'web')->givePermissionTo($pages);
        }
    }

    public function down(): void
    {
        // При откате сохраняем назначения сотрудников: эти роли могли существовать до миграции.
    }
};
