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
        $initData = $request->string('initData')->toString();
        $identity = $verifier->verify($initData);
        $register->execute($identity->id, $identity->username);
        $request->session()->regenerate(true);
        $request->session()->forget('dev_time');
        $request->session()->put([
            'telegram_id' => $identity->id,
            'mini_app_viewer' => $identity->id,
            'mini_app_proof' => hash('sha256', $initData),
        ]);

        return response()->noContent();
    }
}
