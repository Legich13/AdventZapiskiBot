<?php

declare(strict_types=1);

use App\Models\Calendar;
use App\Models\GiftDay;
use App\Models\TelegramUser;
use App\Telegram\Infrastructure\NutgramGateway;
use GuzzleHttp\Psr7\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeader;

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Testing\FakeNutgram;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow('2026-12-07 05:00:00 UTC');
    config(['services.telegram.webhook_secret' => 'wire-test', 'app.url' => 'https://calendar.example', 'queue.default' => 'sync']);
    withHeader('X-Telegram-Bot-Api-Secret-Token', 'wire-test');
    app()->instance(NutgramGateway::class, new class extends NutgramGateway
    {
        public function simulated(): bool
        {
            return false;
        }
    });
});

afterEach(fn () => Carbon::setTestNow());

function botWire(int $count): FakeNutgram
{
    $sdk = Nutgram::fake();
    app()->instance(Nutgram::class, $sdk);
    for ($id = 1; $id <= $count; $id++) {
        $sdk->willReceive(['message_id' => $id, 'date' => now()->timestamp, 'chat' => ['id' => 101, 'type' => 'private']]);
    }

    return $sdk;
}

function startWire(int $update): void
{
    postJson('/telegram/webhook', [
        'update_id' => $update, 'message' => [
            'text' => '/start', 'chat' => ['id' => 101, 'type' => 'private'],
            'from' => ['id' => 101, 'username' => 'winter_reader'],
        ],
    ])->assertNoContent();
}

function assertWireButton(FakeNutgram $sdk, int $index, string $method, string $path, string $label): void
{
    $sdk->assertReply($method, ['chat_id' => 101], $index)
        ->assertRaw(function (Request $request) use ($path, $label, $method): bool {
            $data = FakeNutgram::getActualData($request);
            $markup = $data['reply_markup'];
            if (is_string($markup)) {
                $markup = json_decode($markup, true, flags: JSON_THROW_ON_ERROR);
            }
            expect($markup)->toBe(['inline_keyboard' => [[['text' => $label, 'web_app' => ['url' => 'https://calendar.example'.$path]]]]])
                ->and($data[$method === 'sendPhoto' ? 'caption' : 'text'])->not->toContain('Секрет записки');
            if ($method === 'sendPhoto') {
                expect($request->getHeaderLine('Content-Type'))->toContain('multipart/form-data')
                    ->and((string) $request->getBody())->toContain('filename="calendar-cover.png"');
            }

            return true;
        }, $index);
}

test('старт без вручения отправляет одну кнопку создания или списка календарей', function (bool $author): void {
    $sdk = botWire(1);
    if ($author) {
        TelegramUser::factory()->create(['id' => 101]);
        Calendar::factory()->create(['author_id' => 101]);
    }
    startWire(41);
    startWire(41);
    $sdk->assertCalled('sendMessage', 1)->assertCalled('sendPhoto', 0)->assertCalled('sendWidget', 0);
    assertWireButton($sdk, 0, 'sendMessage', $author ? '/app' : '/app?create=1', $author ? 'Мои календари' : 'Создать календарь');
})->with(['первый вход' => false, 'есть черновик' => true]);

test('первое вручение отправляет одну обложку а утреннее сообщение ведёт на точную дату', function (bool $several): void {
    $sdk = botWire(3);
    $calendar = Calendar::factory()->create(['recipient_username' => 'winter_reader', 'is_self' => false, 'published_at' => now()]);
    GiftDay::factory()->create(['calendar_id' => $calendar->id, 'date' => '2026-12-07', 'text' => 'Секрет записки']);
    if ($several) {
        Calendar::factory()->create(['recipient_username' => 'winter_reader', 'is_self' => false, 'published_at' => now(), 'month' => '2027-01-01']);
    }
    startWire(41);
    startWire(41);
    startWire(42);
    Carbon::setTestNow('2026-12-07 07:00:00 UTC');
    Artisan::call('calendar:morning');
    Artisan::call('calendar:morning');
    $sdk->assertCalled('sendPhoto', 1)->assertCalled('sendMessage', 2)->assertCalled('sendWidget', 0);
    assertWireButton($sdk, 0, 'sendPhoto', $several ? '/app' : '/app/received/'.$calendar->id, $several ? 'Мои календари' : 'Открыть календарь');
    assertWireButton($sdk, 1, 'sendMessage', '/app', 'Мои календари');
    assertWireButton($sdk, 2, 'sendMessage', '/app/received/'.$calendar->id.'/2026-12-07', 'К подарку');
})->with(['один календарь' => false, 'несколько одновременно' => true]);

test('после исправления недоступной обложки повтор того же старта впервые загружает фото', function (): void {
    Calendar::factory()->create(['recipient_username' => 'winter_reader', 'is_self' => false, 'published_at' => now()]);
    $sdk = botWire(1);
    $public = public_path();
    app()->usePublicPath($public.'/несуществующий-каталог');
    try {
        postJson('/telegram/webhook', [
            'update_id' => 71, 'message' => ['text' => '/start', 'chat' => ['id' => 101, 'type' => 'private'], 'from' => ['id' => 101, 'username' => 'winter_reader']],
        ])->assertServerError();
    } finally {
        app()->usePublicPath($public);
    }
    $sdk->assertCalled('sendPhoto', 0);
    startWire(71);
    startWire(71);
    $sdk->assertCalled('sendPhoto', 1);
});
