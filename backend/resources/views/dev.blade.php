@extends('layouts.studio')
@section('title','Мастерская · Декабрь чудес')
@section('nav')<form method="post" action="/dev/staff">@csrf<button>Открыть админку ↗</button></form>@endsection
@section('content')
<section class="cover"><div class="cover-copy"><p class="kicker">Локальный стенд</p><h1>Мастерская<br><em>подарков</em></h1><p>Примерьте роль автора или получателя и загляните в праздничный чат.</p></div><span class="box-art" aria-hidden="true"><i></i><b></b></span></section>
<div class="workgrid"><section class="sheet"><h2>Кто сегодня в боте?</h2>
<form action="/dev/session" method="post">@csrf
<label for="profile">Тестовый профиль</label><select id="profile" name="telegram_id">@foreach($profiles as $id=>$username)<option value="{{ $id }}" @selected($id == $viewer)>{{ $id === 90001 ? 'Автор' : ($id === 90002 ? 'Получатель' : 'Получатель с открытым подарком') }} · {{ '@'.$username }}</option>@endforeach</select>
<label for="clock">Дата и время по Москве</label><input id="clock" type="datetime-local" name="time" required value="{{ session('dev_time','2026-12-07T10:00') }}">
<button class="primary">Применить</button></form>
@if($viewer)<div class="tools"><a class="btn" href="/app">Открыть Mini App ↗</a><form method="post" action="/dev/start">@csrf<button>Запустить /start</button></form><form method="post" action="/dev/morning">@csrf<button>Запустить утреннюю рассылку</button></form></div>@endif
</section><section class="sheet inbox"><h2>Личный чат</h2>
@if(session('notice'))<p role="status" class="notice">{{ session('notice') }}</p>@endif
@if($welcome)<article class="chat-item"><div class="chat-label"><strong>Приветствие бота</strong></div><iframe title="Приветствие бота" srcdoc="{{ $welcome->html }}"></iframe></article>@endif
@forelse($messages as $msg)<article class="chat-item"><div class="chat-label"><strong>{{ $msg->giftDate ? 'Утренний подарок' : 'Календарь' }}</strong><a href="/dev/widget/{{ $msg->id }}" target="_blank" rel="noopener">Открыть ↗</a></div>@if($loop->index < 2)<iframe title="{{ $msg->giftDate ? 'Утренняя карточка' : 'Календарь' }}" loading="lazy" src="/dev/widget/{{ $msg->id }}"></iframe>@endif</article>
@empty<div class="empty"><span aria-hidden="true">✧</span>@if($welcome)<p>Полученных календарей пока нет.</p><small>Создайте свой календарь или запустите /start, чтобы получить подарок.</small>@else<p>Здесь появятся сообщения бота</p><small>Выберите профиль и запустите /start.</small>@endif</div>@endforelse
</section></div>
@endsection
