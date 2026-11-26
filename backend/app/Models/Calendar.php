<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CalendarFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Название модели: Calendar
 * Данная Model предназначена для хранения календаря на выбранный месяц.
 * Бизнес-процесс: Публикация по нику или постоянному Telegram ID и вручение получателю.
 * Источник проверки: docs/backend/calendar.md, CalendarFlowTest и миграция create_calendars.
 * Жизненный цикл: Черновик, публикация, вручение; конфликт возвращает черновик автору.
 * Таблица  ===> calendars
 * Ниже описаны ключи (поля) модели.
 * Связи модели: author и recipient — участники, days — выбранные даты.
 *
 * @property int $id
 * @property int $author_id
 * @property int|null $recipient_id Закреплённый Telegram ID. Может быть известен до вручения.
 * @property string|null $recipient_username
 * @property bool $is_self
 * @property string $month Первое число месяца в формате YYYY-MM-DD.
 * @property Carbon|null $published_at
 * @property Carbon|null $claimed_at Момент вручения, с которого доступны подарки и утренние карточки.
 * @property Carbon|null $claim_conflict_at При вручении обнаружен другой календарь на тот же месяц.
 * @property Carbon|null $created_at Время создания.
 * @property Carbon|null $updated_at Время обновления.
 * @property-read TelegramUser $author
 * @property-read TelegramUser|null $recipient
 * @property-read Collection<int,GiftDay> $days
 */
final class Calendar extends Model
{
    /** @use HasFactory<CalendarFactory> */
    use HasFactory;

    #[\Override]
    protected $table = 'calendars';

    /** @var list<string> */
    #[\Override]
    protected $fillable = ['author_id', 'recipient_id', 'recipient_username', 'is_self', 'month', 'published_at', 'claimed_at', 'claim_conflict_at'];

    /** @return array<string,string> */
    #[\Override]
    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'claimed_at' => 'datetime', 'claim_conflict_at' => 'datetime', 'is_self' => 'boolean'];
    }

    /** @return HasMany<GiftDay, $this> Выбранные даты с записками. */
    public function days(): HasMany
    {
        return $this->hasMany(GiftDay::class)->orderBy('date');
    }

    /** @return BelongsTo<TelegramUser, $this> Создатель. */
    public function author(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class, 'author_id');
    }

    /** @return BelongsTo<TelegramUser, $this> Получатель, закреплённый по Telegram ID. */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class, 'recipient_id');
    }
}
