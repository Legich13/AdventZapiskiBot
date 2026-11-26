<?php

declare(strict_types=1);

namespace App\Http\Requests\Studio;

final class PreviewWidgetQueryRequest extends PreviewSessionRequest
{
    /** @return array<string, string> */
    public function rules(): array
    {
        return ['command' => 'required|string|max:64'];
    }
}
