<?php

declare(strict_types=1);

namespace App\Models;

use App\Admin\StaffPermission;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\Permission\Traits\HasRoles;

/**
 * Название модели ===> User
 * Данная Model предназначена для входа сотрудника в Filament по email и паролю.
 * Бизнес-процесс: поддержка проверяет публикацию, вручение и доставку в отчёте;
 * право редактировать роли выдаётся администратору.
 * Источник проверки: миграция create_users_table, StudioTest и DocumentationAccessTest.
 * Жизненный цикл: создание оператором через advent:staff, вход, назначение ролей.
 * Для локального просмотра мастерская создаёт отдельного сотрудника с ролью local_preview.
 * Таблица  ===> users
 * Ниже описаны ключи и данные учётной записи.
 *
 * @property int $id Внутренний идентификатор сотрудника.
 * @property string $name Отображаемое имя в панели.
 * @property string $email Адрес для входа.
 * @property string $password Хеш пароля.
 * @property string|null $remember_token Токен длительного входа.
 * @property Carbon|null $email_verified_at Момент подтверждения email.
 * @property Carbon|null $created_at Дата создания записи.
 * @property Carbon|null $updated_at Дата последнего изменения.
 *
 * Связи модели: roles и permissions через Spatie. Участники бота хранятся отдельно в telegram_users.
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'admin' || (! app()->isLocal() && $this->hasRole('local_preview'))) {
            return false;
        }

        return $this->canAny([StaffPermission::Overview->value, StaffPermission::Handbook->value]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
