@extends('layouts.studio')
@php
    $month = \Carbon\CarbonImmutable::parse($page->month)->locale('ru');
    $title = mb_convert_case($month->translatedFormat('F Y'), MB_CASE_TITLE);
    $url = '/app/received/'.$page->id;
    $openedCount = count(array_filter($page->opened));
    $total = count($page->opened);
    $percent = round($openedCount / max(1, $total) * 100);
    $selected = $page->selected;
@endphp
@section('title', $title.' · Декабрь чудес')
@section('nav')<a href="/app">Мои календари</a>@endsection
@section('content')
<div class="recipient-card">
    @include('widgets.garland')
    @if($selected === null)
    <p class="advent-year"><span aria-hidden="true">✦</span> ADVENT · {{ $month->year }} <span aria-hidden="true">✦</span></p>
    <h1>{{ mb_convert_case($month->translatedFormat('F'), MB_CASE_TITLE) }} <em>чудес</em></h1>
    <div class="progress-label"><span>Открыто {{ $openedCount }} из {{ $total }}</span><span>{{ $percent }}%</span></div>
    <progress class="gift-progress" value="{{ $openedCount }}" max="{{ max(1, $total) }}" aria-label="Открыто подарков"></progress>
    <div class="month-grid" aria-label="Дни месяца" @unless(app()->isLocal() && session()->has('dev_time')) data-refresh-after-ms="{{ $page->refreshAfterMs }}" @endunless>
        @foreach(['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'] as $weekday)<small aria-hidden="true">{{ $weekday }}</small>@endforeach
        @for($blank = 1; $blank < $month->isoWeekday(); $blank++)<span aria-hidden="true"></span>@endfor
        @for($number = 1; $number <= $month->daysInMonth; $number++)
            @php
                $day = $month->setDay($number);
                $key = $day->toDateString();
                $label = $day->isoFormat('D MMMM');
                $chosen = array_key_exists($key, $page->opened);
                $lastGift = $number === 31 && $month->month === 12 ? ' last' : '';
            @endphp
            @if($chosen && $key <= $page->today)
            <a href="{{ $url }}/{{ $key }}" class="door {{ $page->opened[$key] ? 'read' : 'available' }}{{ $lastGift }}" aria-label="{{ $label }}. {{ $page->opened[$key] ? 'Посмотреть ещё раз' : 'Открыть подарок' }}"><b>{{ $number }}</b><span aria-hidden="true">{{ $page->opened[$key] ? '✓' : '✦' }}</span></a>
            @elseif($chosen)
            <span class="door waiting{{ $lastGift }}" role="img" aria-label="Подарок откроется {{ $label }} в 00:00 по Москве"><b>{{ $number }}</b><span aria-hidden="true">·</span></span>
            @else
            <span class="door quiet" aria-label="{{ $label }}. Без подарка">{{ $number }}</span>
            @endif
        @endfor
    </div>
    <p class="door-legend">✦ Можно открыть &nbsp; ✓ Прочитано &nbsp; · Скоро</p>
    @else
    <p class="day-date">{{ \Carbon\CarbonImmutable::parse($selected)->locale('ru')->isoFormat('D MMMM YYYY') }}</p>
    <h1 id="gift-title">Ваш подарок</h1>
    <button class="unwrap" id="open-gift" data-url="{{ $url }}/{{ $selected }}/open" type="button">
        @include('widgets.gift')
        <span class="unwrap-label">{{ $page->opened[$selected] ? 'Посмотреть ещё раз' : 'Открыть подарок' }}</span>
    </button>
    <p id="gift-error" role="status" hidden></p>
    <article id="gift-note" tabindex="-1" aria-labelledby="note-label" hidden><h2 id="note-label">Записка для вас</h2><p></p></article>
    <a class="back-to-month" href="{{ $url }}">К календарю</a>
    @endif
</div>
@endsection
@push('scripts')<script src="/studio.js" defer></script>@endpush
