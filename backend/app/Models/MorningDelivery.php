<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\MorningDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Название модели: Утренняя доставка.
 * Данная Model предназначена хранить ход отправки отдельной карточки за московскую дату.
 * Бизнес-процесс: Планировщик резервирует получателя и день, воркер записывает результат Bot API.
 * Источник проверки: миграция 2026_01_02_000001 и TelegramWidgetTest.
 * Жизненный цикл: pending → sending → sent, blocked, failed или unknown. Старые pending становятся expired; явный 429 возвращает задание в pending.
 * Таблица  ===> morning_deliveries
 * Ниже описаны ключи: recipient_id с gift_date уникальны; published_message_id указывает подтверждённое сообщение.
 * Связи модели: calendar, recipient, message связывают расписание и фактически выданную карточку.
 *
 * @property int $id
 * @property int $calendar_id
 * @property int $recipient_id
 * @property string $gift_date
 * @property string $status
 * @property Carbon|null $queued_at
 * @property CarbonImmutable $available_at
 * @property int|null $published_message_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class MorningDelivery extends Model
{
    /** @use HasFactory<MorningDeliveryFactory> */
    use HasFactory;

    #[\Override]
    protected $table = 'morning_deliveries';

    /** @var list<string> */
    #[\Override]
    protected $fillable = ['calendar_id', 'recipient_id', 'gift_date', 'status', 'available_at', 'queued_at', 'published_message_id'];

    protected function casts(): array
    {
        return ['available_at' => 'immutable_datetime', 'queued_at' => 'datetime'];
    }

    /** @return BelongsTo<Calendar, $this> */
    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class, 'calendar_id');
    }

    /** @return BelongsTo<TelegramUser, $this> */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class, 'recipient_id');
    }

    /** @return BelongsTo<WidgetMessage, $this> */
    public function message(): BelongsTo
    {
        return $this->belongsTo(WidgetMessage::class, 'published_message_id');
    }
}
