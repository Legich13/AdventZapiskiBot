<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WidgetMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Название модели: Сообщение с виджетом.
 * Данная Model предназначена связать сообщение в личном чате с получателем и календарём.
 * Бизнес-процесс: Выдача записки по нажатию на календарь или утреннюю карточку.
 * Источник проверки: миграция 2026_01_02_000001 и TelegramWidgetTest.
 * Жизненный цикл: Создаётся после ответа Telegram; старые сообщения сохраняют исходный месяц и дату.
 * Таблица  ===> widget_messages
 * Ниже описаны ключи: id идентифицирует привязку; telegram_message_id уникален в чате recipient_id.
 * Связи модели: calendar и recipient принадлежат назначенному подарку и его владельцу.
 *
 * @property int $id
 * @property int $recipient_id
 * @property int $calendar_id
 * @property int|null $telegram_message_id
 * @property string|null $gift_date
 * @property string|null $html
 * @property string $caption
 * @property Carbon $created_at
 */
final class WidgetMessage extends Model
{
    /** @use HasFactory<WidgetMessageFactory> */
    use HasFactory;

    #[\Override]
    protected $table = 'widget_messages';

    /** @var list<string> */
    #[\Override]
    protected $fillable = ['calendar_id', 'recipient_id', 'telegram_message_id', 'gift_date', 'html', 'caption'];

    public const UPDATED_AT = null;

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
}
