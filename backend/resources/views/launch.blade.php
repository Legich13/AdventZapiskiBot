@extends('layouts.studio')
@push('head')<script src="https://telegram.org/js/telegram-web-app.js"></script>@endpush
@section('content')
<section class="sheet signin"><h1>Ваш календарь</h1><p id="signin" role="status">Откройте приложение кнопкой в личном чате с ботом.</p>
<button id="retry-login" type="button" hidden>Попробовать ещё раз</button>
@if(app()->isLocal())<a class="btn" href="/dev">Войти в локальную мастерскую</a>@endif
</section>
@endsection
@push('scripts')<script src="/mini-app-launch.js" defer></script>@endpush
