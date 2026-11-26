<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Studio\PreviewSessionRequest;
use App\Http\Requests\Studio\PreviewWidgetQueryRequest;
use App\Http\Requests\Studio\SelectPreviewProfileRequest;
use App\Studio\Actions\OpenPreviewPanelAction;
use App\Studio\Actions\RunPreviewMorningAction;
use App\Studio\Actions\SelectPreviewProfileAction;
use App\Studio\Actions\StartPreviewChatAction;
use App\Studio\DemoProfiles;
use App\Telegram\Actions\AnswerWidgetAction;
use App\Telegram\Services\WidgetInboxService;
use App\Telegram\Services\WidgetRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DevController
{
    public function index(Request $request, WidgetInboxService $inbox, WidgetRenderer $renderer): View
    {
        $viewer = $request->session()->get('telegram_id');

        return view('dev', [
            'profiles' => DemoProfiles::PEOPLE,
            'viewer' => $viewer,
            'messages' => $inbox->forViewer(is_int($viewer) ? $viewer : null),
            'welcome' => is_int($viewer) ? $renderer->renderWelcome(url('/app')) : null,
        ]);
    }

    public function staff(OpenPreviewPanelAction $login): RedirectResponse
    {
        $login->execute();

        return redirect('/admin');
    }

    public function select(SelectPreviewProfileRequest $request, SelectPreviewProfileAction $select): RedirectResponse
    {
        $select->execute($request->session(), $request->integer('telegram_id'), $request->string('time')->toString());

        return redirect('/dev');
    }

    public function start(PreviewSessionRequest $request, StartPreviewChatAction $start): RedirectResponse
    {
        $received = $start->execute($request->viewer());

        return redirect('/dev')->with('notice', $received ? 'Календарь появился в чате.' : 'Пока нет полученных календарей. Можно создать свой.');
    }

    public function morning(PreviewSessionRequest $request, RunPreviewMorningAction $morning): RedirectResponse
    {
        $morning->execute($request->viewer());

        return redirect('/dev')->with('notice', 'Рассылка проверена на выбранное время.');
    }

    public function widget(PreviewSessionRequest $request, int $message, WidgetInboxService $inbox): View
    {
        return view('preview-widget', ['message' => $message, 'html' => $inbox->previewHtml($request->viewer(), $message)]);
    }

    public function query(PreviewWidgetQueryRequest $request, int $message, AnswerWidgetAction $answer): JsonResponse
    {
        return response()->json($answer->execute($request->viewer(), $message, $request->string('command')->toString()));
    }
}
