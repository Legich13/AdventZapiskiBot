@include('widgets.style')
<style>body{padding:0}</style>
<main id="advent" class="welcome">
@include('widgets.garland')
<header><small>✦ &nbsp; ADVENT &nbsp; ✦</small>
<h1>Маленькие <em>чудеса</em> каждый день</h1></header>
@include('widgets.gift')
<p>Соберите календарь с тёплыми записками и сюрпризами для себя или близкого человека</p>
<a class="gold" href="{{ $launchUrl }}" target="_top">Создать календарь</a>
</main>
