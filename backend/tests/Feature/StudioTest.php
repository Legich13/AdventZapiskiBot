<?php

declare(strict_types=1);

use App\Http\Middleware\PreviewClock;
use App\Models\Calendar;
use App\Models\TelegramUser;
use App\Models\User;
use App\Support\GiftClock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withHeader;
use function Pest\Laravel\withSession;

use Spatie\Permission\Models\Role;
use Symfony\Component\Process\Process;

uses(RefreshDatabase::class);

beforeEach(fn () => withHeader('Sec-Fetch-Site', 'same-origin'));

test('редактор закрепляет адрес по Telegram ID до вручения и позволяет исправить обычный ожидающий ник', function () {
    $recipient = TelegramUser::factory()->create();
    $calendar = Calendar::factory()->create([
        'recipient_id' => $recipient->id, 'recipient_username' => 'december_reader',
        'is_self' => false, 'published_at' => now(),
    ]);
    $page = withSession(['telegram_id' => $calendar->author_id])->get('/app?calendar='.$calendar->id)->assertOk();
    $field = 'id="username"[^>]*';
    expect($page->getContent())->toMatch('/<input '.$field.'\bdisabled\b/');
    $page->assertSee('Получатель закреплён по Telegram ID');

    $calendar->update(['recipient_id' => null]);
    $page = get('/app?calendar='.$calendar->id)->assertOk();
    expect($page->getContent())->not->toMatch('/<input '.$field.'\bdisabled\b/');
    $page->assertSee('Подарок получит владелец этого ника');
});

test('локальная мастерская показывает календарь, редактор и утренние карточки', function () {
    app()->detectEnvironment(fn () => 'local');
    expect(Artisan::call('advent:demo'))->toBe(0);
    expect(Artisan::call('advent:demo'))->toBe(0);
    get('/dev')->assertOk()->assertSee('Мастерская');
    post('/dev/session', ['telegram_id' => 90001, 'time' => '2026-12-07T09:59'])->assertRedirect();
    get('/dev')->assertOk()->assertSee('Запустить утреннюю рассылку');
    get('/app')->assertOk()->assertSee('Для кого')->assertSee('Опубликовать');
    post('/dev/session', ['telegram_id' => 90002, 'time' => '2026-12-07T10:00'])->assertRedirect();
    post('/dev/start')->assertRedirect();
    post('/dev/morning')->assertRedirect();
    post('/dev/morning')->assertRedirect();
    expect(DB::table('morning_deliveries')->where('recipient_id', 90002)->count())->toBe(1);
    $card = DB::table('widget_messages')->where('recipient_id', 90002)->whereNotNull('gift_date')->value('id');
    if (! is_int($card)) {
        throw new UnexpectedValueException('Карточка должна иметь числовой ID.');
    }
    get('/dev/widget/'.$card)->assertOk()->assertSee('TelegramWidget')->assertDontSee('Сегодня мы печём');
    post('/dev/session', ['telegram_id' => 90001, 'time' => '2026-12-07T10:05'])->assertRedirect();
    get('/dev/widget/'.$card)->assertForbidden();
});

test('мастерская показывает приветственный виджет выбранному профилю без календарных сообщений', function (): void {
    app()->detectEnvironment(fn () => 'local');
    get('/dev')->assertOk()->assertDontSee('Приветствие бота');
    $viewer = TelegramUser::factory()->create(['id' => 90001]);

    $response = withSession(['telegram_id' => $viewer->id])->get('/dev')->assertOk();

    $response->assertSee('Приветствие бота')->assertSee('Полученных календарей пока нет.');
    $document = new DOMDocument;
    $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $html = $xpath->evaluate('string(//iframe[@title="Приветствие бота"]/@srcdoc)');
    expect($html)->toBeString();
    assert(is_string($html));
    $preview = new DOMDocument;
    $preview->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $card = new DOMXPath($preview);

    expect($card->evaluate('normalize-space(//h1)'))->toBe('Маленькие чудеса каждый день')
        ->and($card->evaluate('string(//a/@href)'))->toBe(url('/app'))
        ->and($card->evaluate('string(//a/@target)'))->toBe('_top')
        ->and($card->evaluate('normalize-space(//a)'))->toBe('Создать календарь')
        ->and(DB::table('widget_messages')->count())->toBe(0);
});

test('производственная среда закрывает локальную мастерскую при смене окружения', function () {
    app()->detectEnvironment(fn () => 'production');
    get('/dev')->assertNotFound();
    post('/dev/session', ['telegram_id' => 1])->assertNotFound();
});

test('панель доступна сотруднику и отделяет права от обычного входа', function () {
    get('/admin')->assertRedirect();
    $staff = User::factory()->create();
    actingAs($staff)->get('/admin')->assertForbidden();
    $staff->assignRole(Role::findOrCreate('support', 'web'));
    actingAs($staff)->get('/admin')->assertOk()->assertSee('Календари')->assertSee('Получатель')->assertSee('Утренние карточки');
});

test('после локального запроса время возвращается к системному', function () {
    app()->detectEnvironment(fn () => 'local');
    withSession(['dev_time' => '2032-02-29T10:00'])->get('/dev');
    expect(now()->year)->toBe((int) (new DateTimeImmutable)->format('Y'));
});

test('из мастерской можно посмотреть админку под локальным сотрудником', function () {
    app()->detectEnvironment(fn () => 'local');
    post('/dev/staff')->assertRedirect('/admin');
    get('/admin')->assertOk()->assertSee('Календари');
    app()->detectEnvironment(fn () => 'production');
    $viewer = auth()->user();
    $panel = filament()->getPanel('admin');
    if (! $viewer instanceof User || $panel === null) {
        throw new UnexpectedValueException('Ожидался локальный сотрудник и панель.');
    }
    expect($viewer->canAccessPanel($panel))->toBeFalse();
});

test('кеш маршрутов production содержит Mini App и webhook, без маршрутов мастерской', function () {
    $cache = sys_get_temp_dir().'/advent-routes-'.bin2hex(random_bytes(8)).'.php';
    $environment = ['APP_ENV' => 'production', 'APP_ROUTES_CACHE' => $cache];
    $artisan = fn (string ...$args) => (new Process(
        [PHP_BINARY, 'artisan', ...$args], base_path(), $environment
    ))->mustRun()->getOutput();
    try {
        $artisan('route:cache');
        /** @var list<array{uri:string}> $routes */
        $routes = json_decode($artisan('route:list', '--json'), true, flags: JSON_THROW_ON_ERROR);
        $uris = array_column($routes, 'uri');
        expect($uris)->toContain('app', 'telegram/webhook');
        expect(array_filter($uris, fn ($uri) => $uri === 'dev' || str_starts_with($uri, 'dev/')))->toBe([]);
    } finally {
        if (file_exists($cache)) {
            unlink($cache);
        }
    }
});

test('часы мастерской меняют дату подарков и оставляют системное время для лимитов запросов', function (): void {
    app()->detectEnvironment(fn () => 'local');
    Route::middleware(['web', PreviewClock::class])
        ->get('/_test/preview-clocks', static fn () => response()->json([
            'system' => now()->format('Y-m-d'),
            'gift' => GiftClock::now('Europe/Moscow')->format('Y-m-d'),
        ]));

    withSession(['dev_time' => '2032-02-29T10:00'])->get('/_test/preview-clocks')
        ->assertOk()->assertJsonPath('system', now()->format('Y-m-d'))->assertJsonPath('gift', '2032-02-29');
});
