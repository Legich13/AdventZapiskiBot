<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Calendar\Contracts\CalendarRepository;
use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class MiniAppSession implements AuthenticatesRequests
{
    public function __construct(private CalendarRepository $repository) {}

    /** @param Closure(Request):Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->session()->get('telegram_id');
        $identity = is_int($id) ? $this->repository->identity($id) : null;
        abort_if($identity === null, 401, 'Откройте приложение из Telegram.');
        $actor = new GenericUser(['id' => $identity->id]);
        $request->setUserResolver(static fn (): GenericUser => $actor);

        return $next($request);
    }
}
