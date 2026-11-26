<div id="box" style="{{ $day === null ? 'display:none' : '' }}">@include('widgets.gift')</div>
<section id="answer" style="display:none">
<small id="gift-date"></small><h2 id="result-title">Твой подарок!</h2>
<div id="letter"></div><button class="secondary" id="more" style="display:none">Читать дальше →</button>
<button class="gold" id="back">К календарю</button>
</section><p id="notice" role="status"></p>
<script>
(function () {
    const el = id => document.getElementById(id);
    let state = {!! json_encode($state, JSON_THROW_ON_ERROR) !!};
    const day = {!! json_encode($day, JSON_THROW_ON_ERROR) !!};
    const monthName = '{{ $date->getTranslatedMonthName('D MMMM') }}';
    const api = window.TelegramWidget;
    let syncing = !!api;
    let busy = false, pages = [], page = 0;

    function draw() {
        if (day !== null) {
            const done = state.days[day] === 2;
            const button = el('unwrap');
            if (syncing) button.setAttribute('disabled', '');
            else button.removeAttribute('disabled');
            button.textContent = done ? 'Посмотреть ещё раз' : 'Открыть подарок 🎁';
            if (done) el('morning-title').textContent = 'Твой подарок ✧';
            return;
        }
        const dates = Object.keys(state.days);
        const done = dates.filter(d => state.days[d] === 2).length;
        const percent = Math.round(done / Math.max(1, dates.length) * 100);
        el('count').textContent = done + ' из ' + dates.length;
        el('percent').textContent = percent + '%';
        el('fill').style.width = percent + '%';
        el('days').textContent = '';
        for (let n = 1; n <= state.total; n++) {
            const status = state.days[n];
            const button = document.createElement('button');
            if (syncing) button.setAttribute('disabled', '');
            const icon = status === 2 ? '✓' : status === 1 ? '✦' : status === 0 ? '◇' : '·';
            button.className = 'day ' + ({0:'locked', 1:'available', 2:'done'}[status] || 'empty') + (n === 31 && state.month.endsWith('-12') && status !== undefined ? ' last' : '');
            button.innerHTML = '<b>' + n + '</b><span>' + icon + '</span>';
            button.addEventListener('click', () => {
                if (status === undefined) el('notice').textContent = 'На этот день подарок не назначен.';
                else openDay(n);
            });
            el('days').appendChild(button);
        }
    }

    function showPage() {
        el('letter').textContent = pages[page];
        el('more').style.display = page + 1 < pages.length ? '' : 'none';
    }

    function openDay(n) {
        if (busy || syncing) return;
        busy = true;
        el('notice').textContent = 'Открываем…';
        api.request('open:' + n).then(answer => {
            const result = JSON.parse(answer.result);
            if (result.error) { el('notice').textContent = result.error; return; }
            state.days[n] = 2;
            draw();
            el('notice').textContent = '';
            el('front').style.display = 'none';
            el('box').style.display = '';
            el('box').className = result.first ? 'open fresh' : 'open';
            el('answer').className = result.first ? 'fresh' : '';
            el('answer').style.display = '';
            el('result-title').textContent = result.first ? 'Твой подарок!' : 'Твоя записка';
            el('gift-date').textContent = n + ' ' + monthName;
            el('back').textContent = day === null ? 'К календарю' : 'Закрыть записку';
            pages = [];
            let part = '', lines = 0;
            for (const character of result.text) {
                if (part.length >= 90 || lines >= 4) { pages.push(part); part = ''; lines = 0; }
                part += character;
                if (character === '\n') lines++;
            }
            pages.push(part); page = 0; showPage();
        }).catch(() => {el('notice').textContent = 'Не удалось открыть. Нажмите ещё раз.';})
          .finally(() => {busy = false;});
    }
    el('more').addEventListener('click', () => {page++; showPage();});
    el('back').addEventListener('click', () => {
        el('answer').style.display = 'none';
        el('front').style.display = '';
        el('box').style.display = day === null ? 'none' : '';
        el('box').className = '';
    });
    if (day !== null) el('unwrap').addEventListener('click', () => openDay(day));
    draw();
    if (api) {
        api.ready();
        el('notice').textContent = 'Обновляем календарь…';
        api.request('state').then(response => {
            const fresh = JSON.parse(response.result);
            if (fresh.days) state = fresh;
        }).catch(() => {}).finally(() => {
            syncing = false;
            el('notice').textContent = '';
            draw();
        });
    }
})();
</script>
