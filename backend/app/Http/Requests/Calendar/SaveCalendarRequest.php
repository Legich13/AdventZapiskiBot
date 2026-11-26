<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Calendar\DTO\CalendarInputDTO;
use App\Policies\CalendarPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** @phpstan-type EditorInput array{month:string, target:string, username?:string, days:list<array{date:string,text:?string}>} */
final class SaveCalendarRequest extends FormRequest
{
    public function authorize(CalendarPolicy $policy): bool
    {
        $actor = $this->session()->get('telegram_id');
        $id = $this->route('calendar');

        return is_int($actor) && ($id === null || (is_numeric($id) && $policy->mayEdit($actor, (int) $id)));
    }

    public function authorId(): int
    {
        $id = $this->session()->get('telegram_id');
        abort_unless(is_int($id), 401);

        return $id;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'month' => ['required', 'date_format:Y-m', 'regex:/^[1-9][0-9]{3}-/'],
            'target' => ['required', 'in:self,username'],
            'username' => ['exclude_unless:target,username', 'required', 'string', 'regex:/^@?[a-zA-Z][a-zA-Z0-9_]{0,31}$/D'],
            'days' => ['present', 'array', 'list', 'max:31'],
            'days.*' => ['array:date,text'],
            'days.*.date' => ['required', 'date_format:Y-m-d', 'distinct:strict'],
            'days.*.text' => ['present', 'nullable', 'string', 'max:1024'],
        ];
    }

    /** @return array<string,string> */
    public function messages(): array
    {
        return [
            'month.*' => 'Выберите месяц и год.',
            'target.*' => 'Выберите получателя.',
            'username.*' => 'Введите Telegram-ник получателя.',
            'days.*.date.*' => 'Выберите существующую дату месяца один раз.',
            'days.*.text.*' => 'Записка должна помещаться в праздничную карточку.',
        ];
    }

    /** @return array<int,callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $month = $this->string('month')->toString();
            /** @var EditorInput $data Scalar types were checked by the rules above. */
            $data = $validator->getData();
            foreach ($data['days'] as $index => $day) {
                if (! str_starts_with($day['date'], $month.'-')) {
                    $validator->errors()->add("days.$index.date", 'Эта дата находится вне выбранного месяца.');
                }
                if (! CalendarInputDTO::giftFits($day['text'] ?? '')) {
                    $validator->errors()->add("days.$index.text", 'Сократите записку: она не помещается в ответ Telegram.');
                }
            }
        }];
    }

    public function calendarInput(): CalendarInputDTO
    {
        /** @var EditorInput $data */
        $data = $this->validated();
        $self = $data['target'] === 'self';
        $days = [];
        foreach ($data['days'] as $day) {
            $days[$day['date']] = $day['text'] ?? '';
        }

        return new CalendarInputDTO($data['month'].'-01', $self, $self ? null : strtolower(ltrim($data['username'] ?? '', '@')), $days);
    }
}
