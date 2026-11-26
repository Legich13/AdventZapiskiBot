<?php

declare(strict_types=1);

namespace App\Http\Controllers\Calendar;

use App\Calendar\Actions\PublishCalendarAction;
use App\Calendar\Actions\SaveCalendarAction;
use App\Calendar\Services\EditorPageService;
use App\Http\Requests\Calendar\EditorPageRequest;
use App\Http\Requests\Calendar\PublishCalendarRequest;
use App\Http\Requests\Calendar\SaveCalendarRequest;
use App\Http\Resources\Calendar\CalendarIdResource;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;

final class CalendarController
{
    public function index(EditorPageRequest $request, EditorPageService $editor): View
    {
        $page = $editor->forViewer($request->viewerId(), $request->has('calendar') ? $request->integer('calendar') : null);

        return view('app', [
            'viewer' => $page->viewer, 'calendars' => $page->calendars, 'calendar' => $page->calendar,
            'notes' => $page->notes, 'today' => $page->today,
        ]);
    }

    public function store(SaveCalendarRequest $request, SaveCalendarAction $action): CalendarIdResource
    {
        /** @status 201 */
        return new CalendarIdResource($action->execute($request->authorId(), null, $request->calendarInput()));
    }

    public function update(SaveCalendarRequest $request, SaveCalendarAction $action, int $calendar): CalendarIdResource
    {
        return new CalendarIdResource($action->execute($request->authorId(), $calendar, $request->calendarInput()));
    }

    /** @throws ValidationException Если выбранные дни ещё не заполнены. */
    public function publish(PublishCalendarRequest $request, PublishCalendarAction $action, int $calendar): CalendarIdResource
    {
        return new CalendarIdResource($action->execute($request->authorId(), $calendar));
    }
}
