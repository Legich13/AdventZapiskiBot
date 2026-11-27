<?php

declare(strict_types=1);

use App\Models\Calendar;
use App\Models\GiftDay;
use App\Models\TelegramUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeader;
use function Pest\Laravel\withSession;

uses(RefreshDatabase::class);

beforeEach(fn () => Carbon::setTestNow('2026-12-07 06:00:00 UTC'));
afterEach(fn () => Carbon::setTestNow());

/** @return array{TelegramUser, Calendar, string, GiftDay} */
function recipientCalendarFixture(): array
{
    $recipient = TelegramUser::factory()->create();
    $calendar = Calendar::factory()->create([
        'recipient_id' => $recipient->id, 'is_self' => false,
        'published_at' => now(), 'claimed_at' => now(),
    ]);
    $gift = GiftDay::factory()->for($calendar)->create(['date' => '2026-12-07', 'text' => 'Ужин у моря']);
    GiftDay::factory()->for($calendar)->create(['date' => '2026-12-08', 'text' => 'Запечатанный сюрприз']);

    return [$recipient, $calendar, '/app/received/'.$calendar->id, $gift];
}

test('главная разделяет полученные и созданные календари и открывает новый редактор', function (): void {
    [$recipient, , $url] = recipientCalendarFixture();
    $own = Calendar::factory()->create(['author_id' => $recipient->id]);
    asMiniAppViewer($recipient->id);
    get('/app')->assertOk()->assertSee('Мне подарили')->assertSee('Я создаю')->assertSee($url)
        ->assertSee('/app?calendar='.$own->id)->assertSee('/app?create=1')->assertDontSee('Ужин у моря')
        ->assertSee('class="garland"', false)->assertSee('class="gift"', false);
    get('/app?create=1')->assertOk()->assertSee('Новый календарь');
    get('/app?calendar='.$own->id)->assertOk()->assertSee('Ваш календарь');
});

test('вход в другую учётную запись с прежней cookie скрывает личные данные и сохраняет адрес дня', function (): void {
    [$recipient, $calendar, $url] = recipientCalendarFixture();
    withSession(['telegram_id' => $calendar->author_id]);
    get('/app?calendar='.$calendar->id)->assertOk()->assertSee('mini-app-launch.js')->assertDontSee('Запечатанный сюрприз');
    get($url.'/2026-12-07')->assertOk()->assertSee('mini-app-launch.js')->assertDontSee('Ужин у моря');
    withHeader('X-Mini-App-Viewer', (string) $recipient->id);
    get($url.'/2026-12-07')->assertUnauthorized();
    config(['services.telegram.token' => 'recipient-test-token']);
    $launch = signedRecipientLaunch($recipient->id);
    postJson('/app/session', ['initData' => $launch['initData']])->assertNoContent()->assertSessionHas('telegram_id', $recipient->id);
    withHeader('X-Mini-App-Launch', $launch['proof']);
    get($url.'/2026-12-07')->assertOk()->assertSee('Открыть подарок')->assertDontSee('Ужин у моря');
    get('/app?calendar='.$calendar->id)->assertForbidden();
});

test('получатель видит состояние дней и открывает одну записку ровно один раз', function (): void {
    [$recipient, , $url, $gift] = recipientCalendarFixture();
    asMiniAppViewer($recipient->id);
    get($url)->assertOk()->assertSee('ADVENT · 2026')->assertSee('Декабрь <em>чудес</em>', false)
        ->assertSee('0%')->assertSee('Открыто')->assertSee($url.'/2026-12-07')
        ->assertDontSee('Ужин у моря')->assertDontSee('Запечатанный сюрприз');
    get($url.'/2026-12-07')->assertOk()->assertSee('Открыть подарок')->assertDontSee('Ужин у моря');
    expect($gift->refresh()->opened_at)->toBeNull();
    postJson($url.'/2026-12-07/open')->assertOk()->assertExactJson(['text' => 'Ужин у моря', 'first' => true]);
    get($url)->assertOk()->assertSee('50%')->assertSee('Посмотреть ещё раз');
    get($url.'/2026-12-07')->assertOk()->assertSee('Посмотреть ещё раз');
    $first = $gift->refresh()->opened_at?->toISOString();
    expect($first)->not->toBeNull();
    Carbon::setTestNow('2027-01-03 06:00:00 UTC');
    postJson($url.'/2026-12-07/open')->assertOk()->assertExactJson(['text' => 'Ужин у моря', 'first' => false]);
    expect($gift->refresh()->opened_at?->toISOString())->toBe($first);
});

test('неверные даты, чужой и ещё не вручённый календарь закрыты через все входы', function (): void {
    [$recipient, $calendar, $url] = recipientCalendarFixture();
    postJson($url.'/2026-12-07/open')->assertUnauthorized();
    asMiniAppViewer($recipient->id);
    foreach (['2026-11-07', '2026-12-05', '2026-12-32'] as $date) {
        get($url.'/'.$date)->assertNotFound();
        postJson($url.'/'.$date.'/open')->assertNotFound();
    }
    Carbon::setTestNow('2026-12-07 20:59:59 UTC');
    get($url.'/2026-12-08')->assertForbidden()->assertDontSee('Запечатанный сюрприз');
    postJson($url.'/2026-12-08/open')->assertForbidden();
    Carbon::setTestNow('2026-12-07 21:00:00 UTC');
    postJson($url.'/2026-12-08/open')->assertOk()->assertJsonPath('first', true);
    foreach ([$calendar->author_id, $recipient->id] as $id) {
        if ($id === $recipient->id) {
            $calendar->update(['claimed_at' => null]);
        }
        asMiniAppViewer($id);
        get($url)->assertForbidden();
        get($url.'/2026-12-07')->assertForbidden();
        postJson($url.'/2026-12-07/open')->assertForbidden();
    }
});

/** @return array{initData:string,proof:string} */
function signedRecipientLaunch(int $id, int $age = 0): array
{
    $fields = ['auth_date' => (string) (now()->getTimestamp() - $age), 'user' => json_encode(['id' => $id], JSON_THROW_ON_ERROR)];
    $key = hash_hmac('sha256', 'recipient-test-token', 'WebAppData', true);
    $fields['hash'] = hash_hmac('sha256', 'auth_date='.$fields['auth_date']."\nuser=".$fields['user'], $key);
    $init = http_build_query($fields);

    return ['initData' => $init, 'proof' => hash('sha256', $init)];
}

test('после смены аккаунта прежний запуск теряет доступ к чтению и изменениям во всех вкладках', function (): void {
    [$recipient, $calendar, $url, $gift] = recipientCalendarFixture();
    config(['services.telegram.token' => 'recipient-test-token']);
    $a = signedRecipientLaunch($calendar->author_id);
    $b = signedRecipientLaunch($recipient->id);
    $draft = Calendar::factory()->create(['author_id' => $recipient->id]);
    $payload = ['month' => '2026-12', 'target' => 'self', 'days' => []];
    withHeader('Sec-Fetch-Site', 'same-origin');
    postJson('/app/session', ['initData' => $a['initData']])->assertNoContent();
    withHeader('X-Mini-App-Launch', $a['proof']);
    get('/app?calendar='.$calendar->id)->assertOk()->assertSee('Ваш календарь');
    postJson('/app/session', ['initData' => $b['initData']])->assertNoContent();

    withHeader('X-Mini-App-Viewer', (string) $recipient->id);
    postJson('/app/calendars', $payload)->assertUnauthorized();
    get('/app')->assertUnauthorized();
    Pest\Laravel\putJson('/app/calendars/'.$draft->id, $payload)->assertUnauthorized();
    postJson('/app/calendars/'.$draft->id.'/publish')->assertUnauthorized();
    postJson($url.'/2026-12-07/open')->assertUnauthorized();
    expect($gift->refresh()->opened_at)->toBeNull();

    withHeader('X-Mini-App-Viewer', (string) $recipient->id)->withoutHeader('X-Mini-App-Launch');
    get('/app')->assertUnauthorized();
    postJson('/app/calendars', $payload)->assertUnauthorized();
    $expired = signedRecipientLaunch($recipient->id, 7200);
    postJson('/app/session', ['initData' => $expired['initData']])->assertUnauthorized();
    withHeader('X-Mini-App-Launch', $expired['proof']);
    get($url.'/2026-12-07')->assertUnauthorized();

    withHeader('X-Mini-App-Launch', $b['proof']);
    get($url.'/2026-12-07')->assertOk();
    postJson($url.'/2026-12-07/open')->assertOk()->assertJsonPath('first', true);
});

test('коробка и подпись образуют одну кнопку, а будущая пустая дата одинаково закрыта для чтения и открытия', function (): void {
    [$recipient, $calendar, $url] = recipientCalendarFixture();
    asMiniAppViewer($recipient->id);
    $html = get($url.'/2026-12-07')->assertOk()->getContent();
    $dom = new DOMDocument;
    $dom->loadHTML((string) $html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);
    expect($xpath->evaluate('count(//button[@id="open-gift"]/*[local-name()="svg"])'))->toBe(1.0);
    GiftDay::factory()->for($calendar)->create(['date' => '2026-12-31']);
    get($url)->assertOk()->assertSee('door waiting last', false)->assertSee('data-refresh-after-ms', false);
    get($url.'/2026-12-30')->assertForbidden();
    postJson($url.'/2026-12-30/open')->assertForbidden();
});

test('первый переход из уведомления без cookie запускает вход на исходной дате', function (): void {
    [, , $url] = recipientCalendarFixture();
    get($url.'/2026-12-07')->assertOk()->assertSee('mini-app-launch.js')
        ->assertDontSee('Ужин у моря')->assertDontSee('Открыть подарок');
});

test('сессия мастерской сохраняет виртуальное время без автообновления и отклоняет запись прежнего профиля', function (): void {
    [, $calendar, $url] = recipientCalendarFixture();
    app()->detectEnvironment(fn () => 'local');
    withSession(['telegram_id' => $calendar->recipient_id, 'dev_time' => '2026-12-07T08:00']);
    get($url)->assertOk()->assertDontSee('data-refresh-after-ms');
    withHeader('Sec-Fetch-Site', 'same-origin');
    withHeader('X-CSRF-TOKEN', 'token-other-session');
    postJson($url.'/2026-12-07/open')->assertStatus(419);
    withHeader('X-CSRF-TOKEN', session()->token());
    postJson($url.'/2026-12-07/open')->assertOk();
    withHeader('X-Mini-App-Launch', 'obsolete');
    get($url)->assertUnauthorized();
});
