<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TelegramUserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Название модели: TelegramUser
 * Данная Model предназначена для хранения подтверждённого Telegram ID и текущего ника.
 * Бизнес-процесс: Участник создаёт и получает календари.
 * Источник проверки: docs/backend/calendar.md, CalendarFlowTest и миграция create_calendars.
 * Жизненный цикл: Регистрация при входе или /start, обновление имени при следующем визите.
 * Таблица  ===> telegram_users
 * Ниже описаны ключи (поля) модели.
 * Связи модели: authoredCalendars — созданные; receivedCalendars — полученные.
 *
 * @property int $id
 * @property string|null $username Текущий ник в нижнем регистре.
 * @property Carbon|null $created_at Время создания.
 * @property Carbon|null $updated_at Время обновления.
 * @property-read Collection<int,Calendar> $authoredCalendars
 * @property-read Collection<int,Calendar> $receivedCalendars
 */
final class TelegramUser extends Model
{
    /** @use HasFactory<TelegramUserFactory> */
    use HasFactory;

    #[\Override]
    protected $table = 'telegram_users';

    #[\Override]
    public $incrementing = false;

    /** @var list<string> */
    #[\Override]
    protected $fillable = ['id', 'username'];

    /** @return HasMany<Calendar, $this> Созданные календари. */
    public function authoredCalendars(): HasMany
    {
        return $this->hasMany(Calendar::class, 'author_id');
    }

    /** @return HasMany<Calendar, $this> Вручённые календари. */
    public function receivedCalendars(): HasMany
    {
        return $this->hasMany(Calendar::class, 'recipient_id');
    }
}
