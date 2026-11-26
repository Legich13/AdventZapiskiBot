<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Policies\CalendarPolicy;
use Illuminate\Foundation\Http\FormRequest;

final class PublishCalendarRequest extends FormRequest
{
    public function authorize(CalendarPolicy $policy): bool
    {
        $actor = $this->session()->get('telegram_id');
        $id = $this->route('calendar');

        return is_int($actor) && is_numeric($id) && $policy->mayEdit($actor, (int) $id);
    }

    public function authorId(): int
    {
        $id = $this->session()->get('telegram_id');
        abort_unless(is_int($id), 401);

        return $id;
    }
}
