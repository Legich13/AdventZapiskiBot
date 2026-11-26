@include('widgets.style')
<main id="advent" class="morning">
<header><small>✦ &nbsp; {{ mb_strtoupper($label) }} &nbsp; ✦</small>
<h1 id="morning-title">{{ $opened ? 'Твой подарок' : 'Доброе утро' }} <em>✧</em></h1></header>
<section id="front">
<p id="greeting">{{ $opened ? 'Твоя записка ждёт тебя. Её можно прочитать снова.' : $greeting }}</p>
<button id="unwrap" class="gold">{{ $opened ? 'Посмотреть ещё раз' : 'Открыть подарок 🎁' }}</button>
</section>
@include('widgets.script')
</main>
