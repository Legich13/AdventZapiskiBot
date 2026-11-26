<?php

declare(strict_types=1);

use App\Calendar\Actions\PublishCalendarAction;
use App\Models\Calendar;
use App\Models\GiftDay;
use App\Models\MorningDelivery;
use App\Models\TelegramUser;
use App\Models\WidgetMessage;
use App\Studio\DemoProfiles;
use App\Support\GiftClock;
use App\Telegram\Actions\DispatchMorningRemindersAction;
use App\Telegram\Infrastructure\NutgramGateway;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Context\Repository as ContextRepository;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\mock;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeader;
use function Pest\Laravel\withSession;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-12-07 14:00:00', 'Europe/Moscow')->utc());
    config(['queue.default' => 'database']);
});

afterEach(fn () => Carbon::setTestNow());

function todayDeliveryWorker(): void
{
    $worker = app('queue.worker');
    $worker->runNextJob('database', 'default', new WorkerOptions(sleep: 0, maxTries: 1));
}

/** @return Closure():array<mixed> */
function todayDeliveryConcurrentPublication(int $id, int $author): Closure
{
    return static function () use ($id, $author): array {
        Carbon::setTestNow('2026-12-07 11:00:00 UTC');
        config(['queue.default' => 'database']);
        $during = null;
        DB::listen(static function (QueryExecuted $query) use ($id, &$during): void {
            if ($during !== null || ! str_starts_with($query->sql, 'update "morning_deliveries"') || ! str_contains($query->sql, '"queued_at" is null')) {
                return;
            }
            // Публикация остаётся в транзакции, пока другой процесс проверяет рассылку.
            $during = Concurrency::run([static function () use ($id): array {
                Carbon::setTestNow('2026-12-07 11:00:00 UTC');
                config(['queue.default' => 'database']);

                return [
                    app(DispatchMorningRemindersAction::class)->execute(),
                    DB::table('calendars')->where('id', $id)->whereNotNull('published_at')->exists(),
                    DB::table('jobs')->count(),
                ];
            }]);
        });
        app(PublishCalendarAction::class)->execute($author, $id);

        return [$during, DB::table('jobs')->count()];
    };
}

test('публикация себе с десяти часов сразу ставит сегодняшнюю карточку в настоящую очередь', function (string $time): void {
    Carbon::setTestNow(Carbon::parse('2026-12-07 '.$time, 'Europe/Moscow')->utc());
    $calendar = Calendar::factory()->create();
    GiftDay::factory()->create(['calendar_id' => $calendar->id, 'date' => '2026-12-07']);
    $gateway = mock(NutgramGateway::class);
    $gateway->shouldReceive('sendWidget')->once()->with($calendar->author_id, Mockery::type('string'), '7 декабря · Для тебя есть подарок ✨')->andReturn(7001);

    withSession(['telegram_id' => $calendar->author_id])->postJson('/app/calendars/'.$calendar->id.'/publish')->assertSuccessful();

    expect(DB::table('jobs')->count())->toBe(1)
        ->and(WidgetMessage::query()->count())->toBe(0);
    todayDeliveryWorker();
    expect(MorningDelivery::query()->sole()->status)->toBe('sent')
        ->and(WidgetMessage::query()->sole()->gift_date)->toBe('2026-12-07')
        ->and(DB::table('jobs')->count())->toBe(0);
})->with(['ровно в десять' => '10:00:00', 'после десяти' => '14:00:00']);

test('поздний старт сразу ставит сегодняшнюю карточку для получателя по нику и закреплённому ID', function (bool $assigned): void {
    config(['services.telegram.webhook_secret' => 'today-delivery']);
    $recipient = TelegramUser::factory()->create(['username' => 'today_reader', 'bot_blocked_at' => now()->subHour()]);
    $calendar = Calendar::factory()->create([
        'is_self' => false, 'recipient_username' => 'today_reader',
        'recipient_id' => $assigned ? $recipient->id : null, 'published_at' => now(),
    ]);
    GiftDay::factory()->create(['calendar_id' => $calendar->id, 'date' => '2026-12-07']);
    $gateway = mock(NutgramGateway::class);
    $gateway->shouldReceive('welcome')->twice()->with($recipient->id);
    $gateway->shouldReceive('sendWidget')->twice()->with($recipient->id, Mockery::type('string'), 'Декабрь чудес · 2026')->andReturn(7101, 7102);
    $gateway->shouldReceive('sendWidget')->once()->with($recipient->id, Mockery::type('string'), '7 декабря · Для тебя есть подарок ✨')->andReturn(7103);
    $command = ['update_id' => 701, 'message' => [
        'text' => '/start', 'chat' => ['id' => $recipient->id, 'type' => 'private'],
        'from' => ['id' => $recipient->id, 'username' => $assigned ? 'renamed_reader' : 'today_reader'],
    ]];
    withHeader('X-Telegram-Bot-Api-Secret-Token', 'today-delivery');
    postJson('/telegram/webhook', $command)->assertNoContent();

    expect(DB::table('jobs')->count())->toBe(1)
        ->and($recipient->refresh()->bot_blocked_at)->toBeNull();
    postJson('/telegram/webhook', $command)->assertNoContent();
    $command['update_id'] = 702;
    postJson('/telegram/webhook', $command)->assertNoContent();
    expect(DB::table('jobs')->count())->toBe(1);
    todayDeliveryWorker();
    expect(MorningDelivery::query()->sole()->status)->toBe('sent')
        ->and(WidgetMessage::query()->whereNotNull('gift_date')->count())->toBe(1);
})->with(['по нику' => false, 'по закреплённому ID после смены ника' => true]);

test('поздний старт мастерской передаёт виртуальную дату настоящему воркеру', function (): void {
    app()->detectEnvironment(fn () => 'local');
    Carbon::setTestNow('2026-11-26 12:00:00 UTC');
    $recipient = TelegramUser::factory()->create(['id' => 90002, 'username' => DemoProfiles::PEOPLE[90002]]);
    $calendar = Calendar::factory()->create([
        'is_self' => false, 'recipient_username' => $recipient->username, 'published_at' => now(),
    ]);
    GiftDay::factory()->create(['calendar_id' => $calendar->id, 'date' => '2026-12-07']);

    withHeader('Sec-Fetch-Site', 'same-origin');
    post('/dev/session', ['telegram_id' => $recipient->id, 'time' => '2026-12-07T14:00'])->assertRedirect();
    post('/dev/start')->assertRedirect();
    expect(DB::table('jobs')->count())->toBe(1)
        ->and(GiftClock::now('Europe/Moscow')->toDateString())->toBe('2026-11-26');
    Context::flush();
    app(ContextRepository::class)->scope(fn () => todayDeliveryWorker());
    expect(MorningDelivery::query()->sole()->status)->toBe('sent')
        ->and(WidgetMessage::query()->whereNotNull('gift_date')->sole()->gift_date)->toBe('2026-12-07')
        ->and(GiftClock::now('Europe/Moscow')->toDateString())->toBe('2026-11-26');
});

test('публикация до десяти ждёт утреннего планировщика и повторная публикация не создаёт дубль', function (): void {
    Carbon::setTestNow('2026-12-07 06:59:59 UTC');
    $calendar = Calendar::factory()->create();
    GiftDay::factory()->create(['calendar_id' => $calendar->id, 'date' => '2026-12-07']);
    withSession(['telegram_id' => $calendar->author_id]);
    postJson('/app/calendars/'.$calendar->id.'/publish')->assertSuccessful();
    Artisan::call('calendar:morning');
    expect(DB::table('jobs')->count())->toBe(0);

    Carbon::setTestNow('2026-12-07 07:00:00 UTC');
    Artisan::call('calendar:morning');
    postJson('/app/calendars/'.$calendar->id.'/publish')->assertSuccessful();
    Artisan::call('calendar:morning');
    expect(DB::table('jobs')->count())->toBe(1);
    todayDeliveryWorker();
    Artisan::call('calendar:morning');
    expect(MorningDelivery::query()->count())->toBe(1)
        ->and(WidgetMessage::query()->count())->toBe(1)
        ->and(DB::table('jobs')->count())->toBe(0);
});

test('публикация не отправляет прошлый или будущий подарок', function (string $date): void {
    $calendar = Calendar::factory()->create();
    GiftDay::factory()->create(['calendar_id' => $calendar->id, 'date' => $date]);
    withSession(['telegram_id' => $calendar->author_id])->postJson('/app/calendars/'.$calendar->id.'/publish')->assertSuccessful();
    Artisan::call('calendar:morning');
    expect(DB::table('jobs')->count())->toBe(0)
        ->and(MorningDelivery::query()->count())->toBe(0);
})->with(['прошлый день' => '2026-12-06', 'будущий день' => '2026-12-08']);

test('публикация другому человеку ждёт вручения и не запускает чужую сегодняшнюю доставку', function (): void {
    $other = Calendar::factory()->create(['recipient_id' => TelegramUser::factory(), 'published_at' => now(), 'claimed_at' => now()]);
    GiftDay::factory()->create(['calendar_id' => $other->id, 'date' => '2026-12-07']);
    $calendar = Calendar::factory()->create(['is_self' => false, 'recipient_username' => 'waiting_reader']);
    GiftDay::factory()->create(['calendar_id' => $calendar->id, 'date' => '2026-12-07']);
    withSession(['telegram_id' => $calendar->author_id])->postJson('/app/calendars/'.$calendar->id.'/publish')->assertSuccessful();
    expect(DB::table('jobs')->count())->toBe(0);

    $self = Calendar::factory()->create(['author_id' => $calendar->author_id, 'month' => '2026-11-01']);
    GiftDay::factory()->create(['calendar_id' => $self->id, 'date' => '2026-11-26']);
    postJson('/app/calendars/'.$self->id.'/publish')->assertSuccessful();
    expect(DB::table('jobs')->count())->toBe(0);
});

test('планировщик подбирает позднее вручение но исключает невручённое будущее и заблокированного адресата', function (): void {
    foreach (['late', 'unclaimed', 'future', 'blocked', 'draft'] as $state) {
        $recipient = TelegramUser::factory()->create(['bot_blocked_at' => $state === 'blocked' ? now() : null]);
        $calendar = Calendar::factory()->create([
            'recipient_id' => $recipient->id, 'published_at' => $state === 'draft' ? null : now(),
            'claimed_at' => $state === 'unclaimed' ? null : ($state === 'future' ? now()->addHour() : now()),
        ]);
        GiftDay::factory()->create(['calendar_id' => $calendar->id, 'date' => '2026-12-07', 'opened_at' => now()]);
    }
    Artisan::call('calendar:morning');
    expect(DB::table('jobs')->count())->toBe(1);
    todayDeliveryWorker();
    expect(MorningDelivery::query()->sole()->status)->toBe('sent')
        ->and(WidgetMessage::query()->sole()->html)->toContain('Посмотреть ещё раз');
});

test('внешний коммит выпускает отправку а откат отменяет публикацию и резерв', function (bool $commit): void {
    $calendar = Calendar::factory()->create();
    GiftDay::factory()->create(['calendar_id' => $calendar->id, 'date' => '2026-12-07']);
    $gateway = mock(NutgramGateway::class);
    if ($commit) {
        $gateway->shouldReceive('sendWidget')->once()->andReturn(7201);
    } else {
        $gateway->shouldNotReceive('sendWidget');
    }
    DB::beginTransaction();
    withSession(['telegram_id' => $calendar->author_id])->postJson('/app/calendars/'.$calendar->id.'/publish')->assertSuccessful();
    expect(DB::table('jobs')->count())->toBe(0)
        ->and(WidgetMessage::query()->count())->toBe(0);
    if ($commit) {
        DB::commit();
        expect(DB::table('jobs')->count())->toBe(1);
        todayDeliveryWorker();
        expect(MorningDelivery::query()->sole()->status)->toBe('sent');
    } else {
        DB::rollBack();
        expect($calendar->refresh()->published_at)->toBeNull()
            ->and(MorningDelivery::query()->count())->toBe(0)
            ->and(DB::table('jobs')->count())->toBe(0);
    }
})->with(['коммит' => true, 'откат' => false]);

test('откат внешней транзакции отменяет позднее вручение и сегодняшнее задание', function (): void {
    config(['services.telegram.webhook_secret' => 'today-rollback']);
    $recipient = TelegramUser::factory()->create(['username' => 'rollback_reader']);
    $calendar = Calendar::factory()->create(['is_self' => false, 'recipient_username' => $recipient->username, 'published_at' => now()]);
    GiftDay::factory()->create(['calendar_id' => $calendar->id, 'date' => '2026-12-07']);
    $gateway = mock(NutgramGateway::class);
    $gateway->shouldReceive('welcome')->once();
    $gateway->shouldReceive('sendWidget')->once()->with($recipient->id, Mockery::type('string'), 'Декабрь чудес · 2026')->andReturn(7301);

    DB::beginTransaction();
    withHeader('X-Telegram-Bot-Api-Secret-Token', 'today-rollback')->postJson('/telegram/webhook', [
        'update_id' => 731, 'message' => ['text' => '/start', 'chat' => ['id' => $recipient->id, 'type' => 'private'], 'from' => ['id' => $recipient->id, 'username' => $recipient->username]],
    ])->assertNoContent();
    expect(DB::table('jobs')->count())->toBe(0);
    DB::rollBack();
    expect($calendar->refresh()->claimed_at)->toBeNull()
        ->and(MorningDelivery::query()->count())->toBe(0)
        ->and(DB::table('jobs')->count())->toBe(0);
});

test('публикация себе в мастерской сохраняет виртуальные часы до обработки очереди', function (): void {
    app()->detectEnvironment(fn () => 'local');
    Carbon::setTestNow('2026-11-26 12:00:00 UTC');
    $author = TelegramUser::factory()->create(['id' => 90001]);
    $calendar = Calendar::factory()->create(['author_id' => $author->id]);
    GiftDay::factory()->create(['calendar_id' => $calendar->id, 'date' => '2026-12-07']);
    withHeader('Sec-Fetch-Site', 'same-origin');
    post('/dev/session', ['telegram_id' => $author->id, 'time' => '2026-12-07T14:00'])->assertRedirect();
    DB::beginTransaction();
    postJson('/app/calendars/'.$calendar->id.'/publish')->assertSuccessful();
    expect(DB::table('jobs')->count())->toBe(0)
        ->and(GiftClock::now('Europe/Moscow')->toDateString())->toBe('2026-11-26');
    DB::commit();
    Context::flush();
    app(ContextRepository::class)->scope(fn () => todayDeliveryWorker());
    expect(MorningDelivery::query()->sole()->status)->toBe('sent')
        ->and(WidgetMessage::query()->sole()->gift_date)->toBe('2026-12-07')
        ->and(GiftClock::now('Europe/Moscow')->toDateString())->toBe('2026-11-26');
});

test('планировщик в отдельном процессе пересекается с незавершённой публикацией без дубля', function (): void {
    $calendar = Calendar::factory()->create();
    $id = $calendar->id;
    $author = $calendar->author_id;
    GiftDay::factory()->create(['calendar_id' => $id, 'date' => '2026-12-07']);
    $gateway = mock(NutgramGateway::class);
    $gateway->shouldReceive('sendWidget')->once()->andReturn(7401);

    // Дочерний планировщик должен видеть исходный черновик через своё соединение.
    $connection = DB::connection();
    $connection->commit();
    try {
        $result = Concurrency::run([todayDeliveryConcurrentPublication($id, $author)]);
        expect($result)->toBe([[[[0, false, 0]], 1]]);
        Artisan::call('calendar:morning');
        expect(MorningDelivery::query()->where('calendar_id', $id)->count())->toBe(1)
            ->and(DB::table('jobs')->count())->toBe(1);
        todayDeliveryWorker();
        expect(WidgetMessage::query()->where('calendar_id', $id)->count())->toBe(1);
    } finally {
        $deliveryIds = MorningDelivery::query()->where('calendar_id', $id)->pluck('id');
        foreach ($deliveryIds as $deliveryId) {
            assert(is_int($deliveryId));
            DB::table('jobs')->where('payload', 'like', '%SendMorningGift:'.$deliveryId.'"%')->delete();
        }
        MorningDelivery::query()->where('calendar_id', $id)->delete();
        WidgetMessage::query()->where('calendar_id', $id)->delete();
        Calendar::query()->whereKey($id)->delete();
        TelegramUser::query()->whereKey($author)->delete();
        $connection->beginTransaction();
    }
});
