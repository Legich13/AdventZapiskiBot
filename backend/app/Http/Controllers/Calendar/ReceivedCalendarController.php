<?php

declare(strict_types=1);

namespace App\Http\Controllers\Calendar;

use App\Calendar\Actions\OpenGiftAction;
use App\Calendar\Services\RecipientPageService;
use App\Http\Resources\Calendar\OpenedGiftResource;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class ReceivedCalendarController
{
    public function show(Request $request, int $calendar, RecipientPageService $service, ?string $date = null): View
    {
        return view('received', ['page' => $service->page($this->viewerId($request), $calendar, $date)]);
    }

    /** Открыть или перечитать подарок за доступную дату. */
    public function open(Request $request, int $calendar, string $date, OpenGiftAction $action): OpenedGiftResource
    {
        return new OpenedGiftResource($action->execute($this->viewerId($request), $calendar, $date));
    }

    private function viewerId(Request $request): int
    {
        $id = $request->session()->get('telegram_id');
        abort_unless(is_int($id), 401);

        return $id;
    }
}
