<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\GiftDayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Название модели: GiftDay
 * Данная Model предназначена для хранения записки одного дня.
 * Бизнес-процесс: Открытие по московской дате через оба виджета.
 * Источник проверки: docs/backend/calendar.md, CalendarFlowTest и миграция create_calendars.
 * Жизненный цикл: Автор заполняет записку, получатель открывает и перечитывает её.
 * Таблица  ===> gift_days
 * Ниже описаны ключи (поля) модели.
 * Связи модели: calendar — родительский календарь.
 *
 * @property int $id
 * @property int $calendar_id
 * @property string $date Московская календарная дата.
 * @property string $text Записка автора.
 * @property Carbon|null $opened_at
 * @property Carbon|null $created_at Время создания.
 * @property Carbon|null $updated_at Время обновления.
 * @property-read Calendar $calendar
 */
final class GiftDay extends Model
{
    /** @use HasFactory<GiftDayFactory> */
    use HasFactory;

    #[\Override]
    protected $table = 'gift_days';

    /** @var list<string> */
    #[\Override]
    protected $fillable = ['calendar_id', 'date', 'text', 'opened_at'];

    /** @return array<string,string> */
    #[\Override]
    protected function casts(): array
    {
        return ['opened_at' => 'datetime'];
    }

    /** @return BelongsTo<Calendar, $this> Календарь этого дня. */
    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class);
    }
}
