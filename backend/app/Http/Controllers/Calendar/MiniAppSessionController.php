<?php

declare(strict_types=1);

namespace App\Http\Controllers\Calendar;

use App\Calendar\Actions\RegisterTelegramUserAction;
use App\Calendar\Services\MiniAppIdentityVerifier;
use App\Http\Requests\Calendar\MiniAppLoginRequest;
use Illuminate\Http\Response;

final class MiniAppSessionController
{
    public function __invoke(MiniAppLoginRequest $request, MiniAppIdentityVerifier $verifier, RegisterTelegramUserAction $register): Response
    {
        $identity = $verifier->verify($request->string('initData')->toString());
        $register->execute($identity->id, $identity->username);
        $request->session()->regenerate();
        $request->session()->put('telegram_id', $identity->id);

        return response()->noContent();
    }
}
