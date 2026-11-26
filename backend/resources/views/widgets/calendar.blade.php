@include('widgets.style')
<main id="advent">
<svg class="garland" viewBox="0 0 360 83" aria-hidden="true"><g fill="none" stroke="#dfb17b" stroke-width=".8"><path d="M0 8Q45 42 95 -5M265 -5Q315 42 360 8M20 19v20M53 20v35M307 20v35M340 19v20"/></g><g fill="#f4d38f"><circle cx="20" cy="40" r="3"/><circle cx="53" cy="55" r="3"/><circle cx="307" cy="55" r="3"/><circle cx="340" cy="40" r="3"/><path d="M12 4l2 5 5 2-5 2-2 5-2-5-5-2 5-2zm336 0 2 5 5 2-5 2-2 5-2-5-5-2 5-2zM67 29l1.5 4.5 4.5 1.5-4.5 1.5-1.5 4.5-1.5-4.5-4.5-1.5 4.5-1.5zm226 0 1.5 4.5 4.5 1.5-4.5 1.5-1.5 4.5-1.5-4.5-4.5-1.5 4.5-1.5z"/></g></svg>
<header><small>✦ &nbsp; ADVENT · {{ $date->year }} &nbsp; ✦</small>
<h1>{{ $title }} <em>чудес</em></h1></header>
<section id="front">
<div class="stats"><span>Открыто <b id="count"></b></span><span id="percent"></span></div>
<div class="track"><i id="fill"></i></div><div id="days"></div>
<p class="legend">✦ Можно открыть &nbsp; · &nbsp; ✓ Получен</p>
</section>
@include('widgets.script')
</main>
