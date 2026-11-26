<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Policies\CalendarPolicy;
use Illuminate\Foundation\Http\FormRequest;

final class EditorPageRequest extends FormRequest
{
    public function authorize(CalendarPolicy $policy): bool
    {
        $viewer = $this->viewerId();
        $calendar = $this->integer('calendar');

        return $viewer === null || $calendar < 1 || $policy->mayEdit($viewer, $calendar);
    }

    /** @return array<string,list<string>> */
    public function rules(): array
    {
        return ['calendar' => ['sometimes', 'integer', 'min:1']];
    }

    public function viewerId(): ?int
    {
        $id = $this->session()->get('telegram_id');

        return is_int($id) ? $id : null;
    }
}
