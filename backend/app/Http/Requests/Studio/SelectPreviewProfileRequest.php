<?php

declare(strict_types=1);

namespace App\Http\Requests\Studio;

use App\Studio\DemoProfiles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

final class SelectPreviewProfileRequest extends FormRequest
{
    /** @return array<string, list<string|In>> */
    public function rules(): array
    {
        return ['telegram_id' => ['required', 'integer', Rule::in(array_keys(DemoProfiles::PEOPLE))],
            'time' => ['required', 'date_format:Y-m-d\\TH:i']];
    }
}
