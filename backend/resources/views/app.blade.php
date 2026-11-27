@extends('layouts.studio')
@section('nav')@if($editing)<a href="/app">Мои календари</a>@endif @endsection
@section('content')
<section class="cover app-cover">@include('widgets.garland')<div class="cover-copy"><p class="kicker">Календарь подарков</p><h1>Маленькие<br><em>чудеса</em></h1><p>Спрячьте тёплые слова за любимыми датами. Каждый подарок откроется в свой день.</p></div>@include('widgets.gift')</section>
<div class="workgrid{{ $editing ? ' editor' : '' }}">
@if(! $editing)
<section class="sheet"><h2>Мне подарили</h2>
<div class="list">@forelse($received as $item)
<a href="/app/received/{{ $item->id }}"><span>{{ \Carbon\CarbonImmutable::parse($item->month)->locale('ru')->translatedFormat('F Y') }}</span><span aria-hidden="true">›</span></a>
@empty<p>Здесь появятся календари, которые вам подарили.</p>@endforelse</div>
</section>
@else
<form class="sheet" id="editor"><div class="title-row"><h2>{{ $calendar ? 'Ваш календарь' : 'Новый календарь' }}</h2><span class="seal" aria-hidden="true">✦</span></div>
@if($calendar?->claimConflictAt)<p class="notice">У получателя уже есть календарь за этот месяц. Выберите другой адрес для сохранённых подарков.</p>@endif
<fieldset><legend>Для кого готовим сюрприз?</legend><div class="choices">
<label><input type="radio" name="target" value="self" @checked(! $calendar || $calendar->isSelf) @disabled($calendar?->publishedAt)> Для себя</label>
<label><input type="radio" name="target" value="username" @checked($calendar && ! $calendar->isSelf) @disabled($calendar?->publishedAt)> Для другого человека</label></div>
<div id="address">
    <label for="username">Telegram-ник получателя</label>
    <input id="username" placeholder="@username" value="{{ $calendar?->recipientUsername }}" aria-describedby="address-help" @disabled($calendar?->recipientId !== null)>
    <small id="address-help">{{ $calendar?->recipientId !== null ? 'Получатель закреплён по Telegram ID. Смена ника не повлияет на доступ.' : 'Подарок получит владелец этого ника, когда запустит бота.' }}</small>
</div>
</fieldset>
<label for="month">Месяц подарков</label><input id="month" type="month" value="{{ $calendar ? substr($calendar->month,0,7) : '2026-12' }}" @disabled($calendar?->publishedAt)>
<h3>Выберите дни</h3><p class="help">Отметьте даты с подарками и напишите для каждой свою записку.</p>
<div id="dates" class="date-grid"></div><div id="notes"></div><p id="feedback" class="notice" role="status" hidden></p>
<div class="actions"><button class="btn" type="submit" value="save">{{ $calendar?->publishedAt ? 'Сохранить изменения' : 'Сохранить черновик' }}</button>@unless($calendar?->publishedAt)<button class="primary" value="publish" type="submit">Опубликовать</button>@endunless</div></form>
<script type="application/json" id="initial">{!! json_encode(['id'=>$calendar?->id,'published'=>(bool)$calendar?->publishedAt,'today'=>$today,'notes'=>(object) $notes], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR) !!}</script>
@endif
<section class="sheet"><h2>Я создаю</h2>
<div class="list">@forelse($calendars as $item)
<a href="/app?calendar={{ $item->id }}"><span>{{ \Carbon\CarbonImmutable::parse($item->month)->locale('ru')->translatedFormat('F Y') }} <small>{{ $item->isSelf ? 'Для себя' : '@'.$item->recipientUsername }}</small></span><small>{{ $item->claimedAt ? 'Вручён' : ($item->publishedAt ? 'Ждёт получателя' : 'Черновик') }}</small></a>
@empty<p>Выберите даты и приготовьте для них записки.</p>@endforelse</div>
<a class="btn" href="/app?create=1">Создать календарь</a>
</section>
</div>
@endsection
@push('scripts')<script src="/studio.js" defer></script>@endpush
