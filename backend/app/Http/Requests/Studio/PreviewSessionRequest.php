<?php

declare(strict_types=1);

namespace App\Http\Requests\Studio;

use App\Studio\DemoProfiles;
use Illuminate\Foundation\Http\FormRequest;

class PreviewSessionRequest extends FormRequest
{
    public function viewer(): int
    {
        $id = $this->session()->get('telegram_id');

        return is_int($id) ? $id : 0;
    }

    public function authorize(): bool
    {
        return DemoProfiles::contains($this->viewer());
    }
}
