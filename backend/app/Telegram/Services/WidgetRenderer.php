<?php

declare(strict_types=1);

namespace App\Telegram\Services;

use App\Support\GiftClock;
use App\Telegram\Contracts\WidgetRepository;
use App\Telegram\DTO\RenderedWidgetDTO;
use Carbon\CarbonImmutable;

final readonly class WidgetRenderer
{
    public function __construct(private WidgetRepository $messages) {}

    public function render(int $viewerId, int $calendarId, ?string $giftDate): RenderedWidgetDTO
    {
        $state = $this->messages->stateForRecipient($viewerId, $calendarId, GiftClock::now('Europe/Moscow')->toDateString());
        abort_if($state === null, 403);
        $date = CarbonImmutable::parse($giftDate ?? $state->month.'-01');
        $date->locale('ru');
        $winter = $date->month === 12;
        $title = ucfirst($date->translatedFormat('F'));
        $title = mb_convert_case($title, MB_CASE_TITLE);
        $day = $giftDate === null ? null : $date->day;
        $opened = $day !== null && ($state->days[$day] ?? null) === 2;
        $label = $date->isoFormat('D MMMM');
        $greetings = $winter ? [
            'Доброе утро! Под золотой лентой спрятан сегодняшний сюрприз.',
            'Зажглись огоньки, и в календаре вас ждёт подарок.',
            'Время развязать ленту и впустить немного зимнего волшебства.',
        ] : [
            'Пусть день начнётся с приятного сюрприза!',
            'Для вас приготовили маленькую радость. Пора её открыть.',
            'Доброе утро! Этот подарок ждёт именно вас.',
        ];
        $greeting = $greetings[($date->day - 1) % count($greetings)];
        $caption = $day === null ? "$title чудес · ".$date->year : ($opened ? "Твой подарок за $label ✨" : "$label · Для тебя есть подарок ✨");
        $template = $day === null ? 'calendar' : 'morning';
        $html = view('widgets.'.$template, compact('state', 'day', 'date', 'caption', 'title', 'label', 'greeting', 'opened'))->render();

        return new RenderedWidgetDTO($html, $caption);
    }
}
