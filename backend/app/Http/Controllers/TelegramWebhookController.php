<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\TelegramWebhookRequest;
use App\Telegram\Actions\HandleTelegramUpdateAction;
use Illuminate\Http\Response;

final class TelegramWebhookController
{
    public function __invoke(TelegramWebhookRequest $request, HandleTelegramUpdateAction $handle): Response
    {
        $retry = $handle->execute($request->update());

        return $retry === null ? response()->noContent() : response('', 503, ['Retry-After' => (string) $retry]);
    }
}
