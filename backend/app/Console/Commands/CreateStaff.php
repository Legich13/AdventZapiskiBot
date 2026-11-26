<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final class CreateStaff extends Command
{
    #[\Override]
    protected $signature = 'advent:staff {email} {--super-admin : Управление ролями сотрудников}';

    #[\Override]
    protected $description = 'Создать сотрудника панели с паролем';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || User::where('email', $email)->exists()) {
            $this->error('Укажите свободный email.');

            return self::FAILURE;
        }
        $password = $this->secret('Пароль (минимум 12 символов)');
        if (! is_string($password) || mb_strlen($password) < 12) {
            $this->error('Пароль слишком короткий.');

            return self::FAILURE;
        }
        DB::transaction(function () use ($email, $password) {
            $staff = User::create(['name' => $email, 'email' => $email, 'password' => $password]);
            $staff->assignRole(Role::findOrCreate($this->option('super-admin') ? 'super_admin' : 'support', 'web'));
        });
        $this->info('Сотрудник может войти в /admin.');

        return self::SUCCESS;
    }
}
