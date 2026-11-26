<x-filament-panels::page>
<label>Найти по нику или Telegram ID
    <x-filament::input.wrapper><x-filament::input type="search" wire:model.live.debounce.350ms="search" /></x-filament::input.wrapper>
</label>
<x-filament::section heading="Календари">
<div style="overflow:auto"><table class="fi-ta-table"><thead><tr>@foreach(['Месяц','Автор','Адресат','Получатель','Опубликован · МСК','Вручён · МСК','Открыто','Последнее открытие · МСК'] as $heading)<th class="fi-ta-header-cell">{{ $heading }}</th>@endforeach</tr></thead>
<tbody>@forelse($calendars as $calendar)<tr class="fi-ta-row">
@foreach([substr($calendar->month,0,7),$calendar->author,$calendar->address,$calendar->recipient ?? 'Ожидает',
$calendar->publishedAt ? \Carbon\CarbonImmutable::parse($calendar->publishedAt)->timezone('Europe/Moscow')->format('d.m.Y H:i') : 'Черновик',
$calendar->claimedAt ? \Carbon\CarbonImmutable::parse($calendar->claimedAt)->timezone('Europe/Moscow')->format('d.m.Y H:i') : '—',
$calendar->openedCount, $calendar->lastOpened ? \Carbon\CarbonImmutable::parse($calendar->lastOpened)->timezone('Europe/Moscow')->format('d.m.Y H:i') : '—' ] as $cell)<td class="fi-ta-cell" style="padding:8px">{{ $cell }}</td>@endforeach
</tr>@empty<tr><td colspan="8">Календарей пока нет.</td></tr>@endforelse</tbody></table></div>{{ $calendars->links() }}</x-filament::section>
<x-filament::section heading="Пользователи Telegram">
@forelse($people as $person)<p>{{ $person->id }} · {{ $person->username ? '@'.$person->username : 'Без ника' }}</p>@empty<p>Пользователей пока нет.</p>@endforelse{{ $people->links() }}
</x-filament::section>
<x-filament::section heading="Утренние карточки">
@forelse($deliveries as $delivery)<p>{{ $delivery->giftDate }} · ID {{ $delivery->recipientId }} · {{ ['sent'=>'Принята Telegram','blocked'=>'Бот заблокирован','unknown'=>'Результат неизвестен','expired'=>'Срок истёк','pending'=>'Ожидает','sending'=>'Отправляется','failed'=>'Ошибка'][$delivery->status] ?? $delivery->status }}</p>@empty<p>Отправок пока нет.</p>@endforelse{{ $deliveries->links() }}
</x-filament::section>
</x-filament-panels::page>
