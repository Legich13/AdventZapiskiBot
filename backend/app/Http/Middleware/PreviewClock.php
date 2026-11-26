<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\GiftClock;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Log\Context\Repository;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

final readonly class PreviewClock
{
    public function __construct(private Repository $context) {}

    /** @param Closure(Request):Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $localTime = $request->session()->get('dev_time');
        if (! app()->isLocal() || ! is_string($localTime)) {
            return $next($request);
        }

        return $this->context->scope(
            fn (): Response => $next($request),
            hidden: [GiftClock::PREVIEW_TIME => Carbon::parse($localTime, 'Europe/Moscow')->utc()],
        );
    }
}
