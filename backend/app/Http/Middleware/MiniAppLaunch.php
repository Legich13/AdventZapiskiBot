<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class MiniAppLaunch
{
    /** @param Closure(Request):Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();
        $id = $session->get('telegram_id');
        $proof = $request->header('X-Mini-App-Launch');
        $viewer = $request->header('X-Mini-App-Viewer');
        $studio = app()->isLocal() && is_int($id) && $session->has('dev_time');
        if ($request->isMethodSafe() && $proof === null && $viewer === null && ! $studio) {
            return response()->view('launch')->header('Cache-Control', 'private, no-store');
        }
        if ($studio) {
            abort_if($proof !== null || $viewer !== null, 401);
            if (! $request->isMethodSafe()) {
                $token = $request->header('X-CSRF-TOKEN');
                abort_unless(is_string($token) && hash_equals($session->token(), $token), 419);
            }
        } else {
            $expected = $session->get('mini_app_proof');
            abort_unless(is_int($id) && $session->get('mini_app_viewer') === $id
                && is_string($expected) && is_string($proof) && hash_equals($expected, $proof), 401);
        }
        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
