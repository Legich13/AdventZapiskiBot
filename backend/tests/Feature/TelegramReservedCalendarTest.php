<?php

declare(strict_types=1);

use App\Calendar\Actions\OpenGiftAction;
use App\Models\Calendar;
use App\Models\GiftDay;
use App\Models\MorningDelivery;
use App\Models\TelegramUser;
use App\Models\WidgetMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\withHeader;

uses(RefreshDatabase::class);

function reservedDecemberCalendar(): Calendar
{
    $recipient = TelegramUser::factory()->create(['username' => 'first_name']);

    return Calendar::factory()->create([
        'is_self' => false, 'recipient_id' => $recipient->id, 'recipient_username' => 'first_name',
        'month' => '2026-12-01', 'published_at' => now(),
    ]);
}

afterEach(fn () => Carbon::setTestNow());

test('список автора показывает новые календари перед ранее импортированным подарком с большим ID', function (): void {
    Carbon::setTestNow('2026-11-26 10:00:00 UTC');
    $imported = Calendar::factory()->create(['id' => 800_000_000, 'created_at' => now()->subDay()]);
    $first = Calendar::factory()->create(['author_id' => $imported->author_id]);
    $second = Calendar::factory()->create(['author_id' => $imported->author_id]);

    asMiniAppViewer($imported->author_id)->get('/app')->assertOk()->assertSeeInOrder([
        'href="/app?calendar='.$second->id.'"',
        'href="/app?calendar='.$first->id.'"',
        'href="/app?calendar='.$imported->id.'"',
    ], false);
});

test('календарь вручается по закреплённому ID при старте и сохраняет время вручения и открытия', function (?string $newUsername): void {
    Carbon::setTestNow('2026-12-01 07:00:00 UTC');
    config(['queue.default' => 'sync', 'services.telegram.webhook_secret' => 'reserved-calendar']);
    withHeader('X-Telegram-Bot-Api-Secret-Token', 'reserved-calendar');
    $calendar = reservedDecemberCalendar();
    $recipientId = $calendar->recipient_id;
    assert(is_int($recipientId));
    $first = GiftDay::factory()->create(['calendar_id' => $calendar->id, 'date' => '2026-12-01', 'text' => 'Вопрос дня']);
    GiftDay::factory()->create(['calendar_id' => $calendar->id, 'date' => '2026-12-02']);
    $start = static fn (int $update, int $id, ?string $name) => postJson('/telegram/webhook', [
        'update_id' => $update, 'message' => [
            'text' => '/start', 'chat' => ['id' => $id, 'type' => 'private'],
            'from' => array_filter(['id' => $id, 'username' => $name]),
        ],
    ]);

    $start(1, 333, 'first_name')->assertNoContent();
    expect($calendar->refresh()->claimed_at)->toBeNull()
        ->and(WidgetMessage::whereNotNull('calendar_id')->count())->toBe(0);
    Artisan::call('calendar:morning');
    expect(MorningDelivery::count())->toBe(0);

    Carbon::setTestNow('2026-12-01 07:01:00 UTC');
    $start(2, $recipientId, $newUsername)->assertNoContent();
    expect($calendar->refresh()->claimed_at?->timestamp)->toBe(now()->timestamp);
    $message = WidgetMessage::query()->whereNotNull('photo')->sole();
    expect(MorningDelivery::query()->where('gift_date', '2026-12-01')->sole()->status)->toBe('sent');
    expect($message->recipient_id)->toBe($recipientId)
        ->and($message->calendar_id)->toBe($calendar->id)
        ->and($message->app_path)->toBe('/app/received/'.$calendar->id)
        ->and(app(OpenGiftAction::class)->execute($recipientId, $calendar->id, '2026-12-01')->text)->toBe('Вопрос дня');
    $opened = $first->refresh()->opened_at?->timestamp;
    expect($opened)->toBe(now()->timestamp);

    Carbon::setTestNow('2026-12-02 07:00:00 UTC');
    $start(3, $recipientId, $newUsername)->assertNoContent();
    expect($calendar->refresh()->claimed_at?->timestamp)->toBe($opened)
        ->and($first->refresh()->opened_at?->timestamp)->toBe($opened);
    Artisan::call('calendar:morning');
    $delivery = MorningDelivery::query()->where('gift_date', '2026-12-02')->sole();
    expect($delivery->recipient_id)->toBe($recipientId)
        ->and($delivery->gift_date)->toBe('2026-12-02')
        ->and($delivery->status)->toBe('sent');
})->with(['ник изменён' => 'renamed', 'ник удалён' => [null]]);

test('до вручения по ID автор может менять будущую записку только для закреплённого адресата', function (): void {
    Carbon::setTestNow('2026-11-26 10:00:00 UTC');
    $calendar = reservedDecemberCalendar();
    $gift = GiftDay::factory()->create(['calendar_id' => $calendar->id, 'date' => '2026-12-01']);
    $payload = [
        'month' => '2026-12', 'target' => 'username', 'username' => 'first_name',
        'days' => [['date' => $gift->date, 'text' => 'Выбери любимый фильм.']],
    ];
    $url = '/app/calendars/'.$calendar->id;
    asMiniAppViewer($calendar->author_id)->putJson($url, $payload)->assertSuccessful();
    expect($gift->refresh()->text)->toBe('Выбери любимый фильм.');
    $payload['username'] = 'another_person';
    putJson($url, $payload)->assertUnprocessable();
    expect($calendar->refresh()->recipient_username)->toBe('first_name');
});
