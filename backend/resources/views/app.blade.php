@extends('layouts.studio')
@section('nav')@if($viewer)<span>{{ $viewer->username ? '@'.$viewer->username : 'Мой календарь' }}</span>@endif @endsection
@push('head')
@if(! $viewer && !app()->isLocal())<script src="https://telegram.org/js/telegram-web-app.js"></script>@endif
@endpush
@section('content')
<section class="cover"><div class="cover-copy"><p class="kicker">Календарь подарков</p><h1>Маленькие<br><em>чудеса</em></h1><p>Спрячьте тёплые слова за любимыми датами. Каждый подарок откроется в свой день.</p></div><span class="box-art" aria-hidden="true"><i></i><b></b></span></section>
@if(! $viewer)
<section class="sheet signin"><h2>Откройте календарь в Telegram</h2><p id="signin" role="status">Зайдите в личный чат с ботом и нажмите «Создать календарь».</p>@if(app()->isLocal())<a class="btn" href="/dev">Войти в локальную мастерскую</a>@endif</section>
@else
<div class="workgrid editor"><form class="sheet" id="editor"><div class="title-row"><h2>{{ $calendar ? 'Ваш календарь' : 'Новый календарь' }}</h2><span class="seal" aria-hidden="true">✦</span></div>
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
<aside class="sheet"><h2>Мои календари</h2><div class="list">@forelse($calendars as $item)<a href="/app?calendar={{ $item->id }}"><span>{{ \Carbon\CarbonImmutable::parse($item->month)->locale('ru')->translatedFormat('F Y') }} <small>{{ $item->isSelf ? 'Для себя' : '@'.$item->recipientUsername }}</small></span><small>{{ $item->claimedAt ? 'Вручён' : ($item->publishedAt ? 'Ждёт получателя' : 'Черновик') }} ›</small></a>@empty<p>Здесь появится ваш первый сохранённый календарь.</p>@endforelse</div>@if($calendar)<a href="/app" class="btn">Создать ещё</a>@endif</aside></div>
<script type="application/json" id="initial">{!! json_encode(['id'=>$calendar?->id,'published'=>(bool)$calendar?->publishedAt,'today'=>$today,'notes'=>(object) $notes], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR) !!}</script>
@endif
@endsection
@push('scripts')<script src="/studio.js" defer></script>@endpush
