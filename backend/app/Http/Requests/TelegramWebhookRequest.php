<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Telegram\DTO\StartUpdateDTO;
use App\Telegram\DTO\WidgetQueryDTO;
use Illuminate\Foundation\Http\FormRequest;

final class TelegramWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        $secret = config('services.telegram.webhook_secret');

        return is_string($secret) && $secret !== '' && hash_equals($secret, $this->header('X-Telegram-Bot-Api-Secret-Token') ?? '');
    }

    /** @return array<string,string> */
    public function rules(): array
    {
        return [
            'update_id' => 'required|integer|min:0',
            'message' => 'sometimes|array',
            'message.text' => 'sometimes|string',
            'message.chat.id' => 'sometimes|integer',
            'message.chat.type' => 'sometimes|string',
            'message.from.id' => 'sometimes|integer|min:1',
            'message.from.username' => 'nullable|string|max:32',
            'widget_query' => 'sometimes|array|min:1',
            'widget_query.id' => 'required_with:widget_query|string|max:128',
            'widget_query.from.id' => 'required_with:widget_query|integer|min:1',
            'widget_query.chat.type' => 'sometimes|string',
            'widget_query.chat.id' => 'sometimes|integer',
            'widget_query.message_id' => 'sometimes|integer|min:1',
            'widget_query.data' => 'required_with:widget_query|string|max:64',
        ];
    }

    /** Остальные типы Update Telegram пропускаются с успешным подтверждением. */
    public function update(): StartUpdateDTO|WidgetQueryDTO|null
    {
        if ($this->has('widget_query')) {
            $viewer = $this->integer('widget_query.from.id');
            $private = $this->input('widget_query.chat.type') === 'private' && $viewer === $this->integer('widget_query.chat.id');

            return new WidgetQueryDTO(
                (string) $this->string('widget_query.id'), $viewer,
                $private ? $this->integer('widget_query.message_id') : null,
                (string) $this->string('widget_query.data'),
            );
        }
        $viewer = $this->integer('message.from.id');
        if ($this->input('message.chat.type') !== 'private' || $viewer < 1 || $viewer !== $this->integer('message.chat.id')
            || ! preg_match('/^\/start(?:\s|$)/D', (string) $this->string('message.text'))) {
            return null;
        }

        return new StartUpdateDTO($this->integer('update_id'), $viewer, $this->filled('message.from.username') ? (string) $this->string('message.from.username') : null);
    }
}
