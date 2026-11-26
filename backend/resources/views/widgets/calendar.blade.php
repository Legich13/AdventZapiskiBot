@include('widgets.style')
<main id="advent">
@include('widgets.garland')
<header><small>✦ &nbsp; ADVENT · {{ $date->year }} &nbsp; ✦</small>
<h1>{{ $title }} <em>чудес</em></h1></header>
<section id="front">
<div class="stats"><span>Открыто <b id="count"></b></span><span id="percent"></span></div>
<div class="track"><i id="fill"></i></div><div id="days"></div>
<p class="legend">✦ Можно открыть &nbsp; · &nbsp; ✓ Получен</p>
</section>
@include('widgets.script')
</main>
