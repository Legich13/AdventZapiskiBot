<?php

declare(strict_types=1);

use App\Jobs\SendMorningGift;
use App\Models\Calendar;
use App\Models\GiftDay;
use App\Models\MorningDelivery;
use App\Models\TelegramUser;
use App\Models\WidgetMessage;
use App\Telegram\Actions\AnswerWidgetAction;
use App\Telegram\Actions\DeliverMorningGiftAction;
use App\Telegram\Actions\DispatchMorningRemindersAction;
use App\Telegram\Actions\SendWidgetAction;
use App\Telegram\DTO\WidgetInboxItemDTO;
use App\Telegram\Infrastructure\NutgramGateway;
use App\Telegram\Services\MorningRateLimiter;
use App\Telegram\Services\WidgetInboxService;
use Carbon\Carbon;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Jobs\DatabaseJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\View;

use function Pest\Laravel\mock;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeader;
use function Pest\Laravel\withSession;

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Exceptions\TelegramException;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

/** @return array<array-key, mixed> */
function widgetReply(string $data): array
{
    $reply = json_decode($data, true, flags: JSON_THROW_ON_ERROR);
    assert(is_array($reply));

    return $reply;
}

function widgetTime(string $local): void
{
    Carbon::setTestNow(Carbon::parse($local, 'Europe/Moscow')->utc());
}

function widgetCalendar(int $recipient = 101, string $claim = '2026-12-01 00:00:00'): int
{
    TelegramUser::factory()->create(['id' => $recipient, 'username' => 'winter'.$recipient]);
    $calendar = Calendar::factory()->create([
        'author_id' => $recipient, 'recipient_id' => $recipient, 'recipient_username' => 'winter'.$recipient,
        'month' => '2026-12-01', 'published_at' => $claim, 'claimed_at' => $claim,
    ])->id;
    foreach ([7, 8] as $day) {
        GiftDay::factory()->create([
            'calendar_id' => $calendar, 'date' => "2026-12-0$day", 'text' => "Секретная записка $day",
        ]);
    }

    return $calendar;
}

beforeEach(function () {
    widgetTime('2026-12-07 10:00:00');
    config(['queue.default' => 'sync']);
});

afterEach(fn () => Carbon::setTestNow());

test('рассылка в десять часов создаёт одну карточку на получателя, включая уже открытый подарок', function () {
    widgetCalendar();
    $opened = widgetCalendar(102);
    widgetCalendar(103, '2026-12-07 07:00:01');
    DB::table('gift_days')->where(['calendar_id' => $opened, 'date' => '2026-12-07'])->update(['opened_at' => '2026-12-07 05:00:00']);
    widgetTime('2026-12-07 09:59:59');
    expect(Artisan::call('calendar:morning'))->toBe(0);
    expect(DB::table('widget_messages')->count())->toBe(0);
    widgetTime('2026-12-07 10:00:00');
    expect(Artisan::call('calendar:morning'))->toBe(0);
    expect(Artisan::call('calendar:morning'))->toBe(0);
    $messages = WidgetMessage::query()->orderBy('recipient_id')->get();
    expect($messages)->toHaveCount(2)
        ->and($messages->firstOrFail()->html)->toContain('Открыть подарок')
        ->and($messages->sole('recipient_id', 102)->html)->toContain('Посмотреть ещё раз')
        ->and($messages->firstOrFail()->html.$messages->firstOrFail()->caption)->not->toContain('Секретная записка')
        ->and(DB::table('morning_deliveries')->where('status', 'sent')->count())->toBe(2);
    widgetTime('2026-12-09 10:01:00');
    expect(Artisan::call('calendar:morning'))->toBe(0);
    expect(DB::table('widget_messages')->count())->toBe(2);
});

test('основной и утренний виджеты разделяют открытие, даты и авторизацию', function () {
    $calendar = widgetCalendar();
    $widgets = app(AnswerWidgetAction::class);
    $full = app(SendWidgetAction::class)->execute(101, $calendar);
    app(DispatchMorningRemindersAction::class)->execute();
    $morning = WidgetMessage::query()->whereNotNull('gift_date')->sole()->id;
    $answer = fn (int $viewer, int $message, string $cmd) => json_decode($widgets->execute($viewer, $message, $cmd), true);
    expect($answer(102, $full, 'open:7'))->toHaveKey('error')
        ->and($answer(101, $full, 'open:8'))->toHaveKey('error')
        ->and($answer(101, $full, 'open:7'))->toMatchArray(['text' => 'Секретная записка 7', 'first' => true]);
    widgetTime('2027-01-01 10:00:00');
    expect($answer(101, $morning, 'open:8'))->toHaveKey('error')
        ->and($answer(101, $morning, 'open:7'))->toMatchArray(['text' => 'Секретная записка 7', 'first' => false]);
    foreach (WidgetMessage::query()->get() as $message) {
        $html = $message->html;
        assert(is_string($html));
        expect(strlen($html))->toBeLessThanOrEqual(32768)
            ->and(strlen(gzcompress($html, 9) ?: throw new RuntimeException('Ошибка zlib')))->toBeLessThanOrEqual(4096)
            ->and(str_contains($html, 'prefers-reduced-motion'))->toBeTrue()
            ->and(str_contains($html, 'Секретная записка'))->toBeFalse();
    }
});

test('неверный секрет webhook отклоняется до обработки обновления', function () {
    config(['services.telegram.webhook_secret' => 'winter-test']);
    postJson('/telegram/webhook', ['update_id' => 1])->assertForbidden();
    withHeader('X-Telegram-Bot-Api-Secret-Token', 'winter-test')
        ->postJson('/telegram/webhook', ['update_id' => 2])->assertSuccessful();
});

test('рассылка классифицирует явные отказы и неопределённую доставку', function (int $code, string $expected) {
    widgetCalendar();
    $error = $code === 0 ? new RuntimeException('connection lost') : new TelegramException('Telegram', $code, parameters: ['retry_after' => 37]);
    mock(NutgramGateway::class)->shouldReceive('sendWidget')->once()->andThrow($error);
    app(DispatchMorningRemindersAction::class)->execute();
    $delivery = MorningDelivery::query()->sole();
    expect($delivery->status)->toBe($expected);
    if ($expected === 'pending') {
        expect($delivery->available_at->toDateTimeString())->toBe(now()->addSeconds(37)->toDateTimeString());
    }
    (new SendMorningGift($delivery->id))->handle(app(DeliverMorningGiftAction::class));
    expect(DB::table('widget_messages')->count())->toBe(0);
})->with([
    'лимит' => [429, 'pending'],
    'бот заблокирован' => [403, 'blocked'],
    'пропал ответ' => [0, 'unknown'],
]);

test('после рестарта просроченная отправка с неопределённым исходом не повторяется', function () {
    widgetCalendar();
    Queue::fake();
    app(DispatchMorningRemindersAction::class)->execute();
    DB::table('morning_deliveries')->update(['status' => 'sending', 'updated_at' => now()->subMinutes(10)]);
    app(DispatchMorningRemindersAction::class)->execute();
    expect(DB::table('morning_deliveries')->value('status'))->toBe('unknown')
        ->and(DB::table('widget_messages')->count())->toBe(0);
});

test('интерактивные запросы ограничены отдельно для каждого получателя', function () {
    $id = widgetCalendar();
    $widgets = app(AnswerWidgetAction::class);
    $message = app(SendWidgetAction::class)->execute(101, $id);
    for ($tap = 0; $tap < 5; $tap++) {
        expect(json_decode($widgets->execute(101, $message, 'state'), true))->toHaveKey('days');
    }
    expect(json_decode($widgets->execute(101, $message, 'state'), true))->toHaveKey('error');
});

test('в мастерской лимит нажатий сбрасывается через пять реальных секунд при неизменной дате подарка', function (): void {
    app()->detectEnvironment(fn () => 'local');
    config(['cache.default' => 'database', 'cache.limiter' => 'database']);
    widgetTime('2026-11-26 12:00:00');
    $calendar = widgetCalendar(90002);
    $message = app(SendWidgetAction::class)->execute(90002, $calendar);
    withHeader('Sec-Fetch-Site', 'same-origin');
    withSession(['telegram_id' => 90002, 'dev_time' => '2026-12-07T10:00']);
    $request = static function (string $command) use ($message): array {
        $data = postJson('/dev/widget/'.$message.'/query', ['command' => $command])->assertOk()->json();
        assert(is_string($data));

        return widgetReply($data);
    };
    for ($tap = 0; $tap < 5; $tap++) {
        expect($request('state')['days'])->toBe([7 => 1, 8 => 0]);
    }
    expect($request('state'))->toHaveKey('error');
    expect($request('open:7'))->toMatchArray(['text' => 'Секретная записка 7', 'first' => true]);
    for ($tap = 0; $tap < 4; $tap++) {
        expect($request('open:7')['first'])->toBeFalse();
    }
    expect($request('open:7'))->toHaveKey('error');
    widgetTime('2026-11-26 12:00:06');
    expect($request('open:7'))->toMatchArray(['text' => 'Секретная записка 7', 'first' => false]);
});

test('команда старта даёт кнопку редактора, а повтор одного обновления не дублирует приветствие', function () {
    config(['services.telegram.webhook_secret' => 'winter-test']);
    $gateway = mock(NutgramGateway::class);
    $gateway->shouldReceive('welcome')->once()->with(8001);
    $command = ['update_id' => 98, 'message' => [
        'message_id' => 3, 'chat' => ['id' => 8001, 'type' => 'private'],
        'from' => ['id' => 8001, 'username' => 'winter8001'], 'text' => '/start',
    ]];
    foreach ([1, 2] as $attempt) {
        withHeader('X-Telegram-Bot-Api-Secret-Token', 'winter-test')
            ->postJson('/telegram/webhook', $command)->assertSuccessful();
    }
});

test('задержанное задание со вчерашней датой не отправляет сегодняшнее поздравление', function () {
    widgetCalendar();
    Queue::fake();
    app(DispatchMorningRemindersAction::class)->execute();
    $delivery = MorningDelivery::query()->sole()->id;
    widgetTime('2026-12-08 00:00:00');
    (new SendMorningGift($delivery))->handle(app(DeliverMorningGiftAction::class));
    expect(DB::table('morning_deliveries')->where('id', $delivery)->value('status'))->toBe('expired')
        ->and(DB::table('widget_messages')->count())->toBe(0);
});

test('виджет в личном чате сверяет отправителя, чат и номер сообщения', function () {
    config(['services.telegram.webhook_secret' => 'winter-test']);
    $calendar = widgetCalendar();
    $id = app(SendWidgetAction::class)->execute(101, $calendar);
    DB::table('widget_messages')->where('id', $id)->update(['telegram_message_id' => 56]);
    $gateway = mock(NutgramGateway::class);
    $gateway->shouldReceive('answer')->once()->withArgs(fn (string $query, string $data): bool => $query === 'q1' && array_key_exists('error', widgetReply($data)));
    $gateway->shouldReceive('answer')->once()->withArgs(fn (string $query, string $data): bool => $query === 'q2' && (widgetReply($data)['text'] ?? '') === 'Секретная записка 7');
    foreach ([102, 101] as $index => $from) {
        withHeader('X-Telegram-Bot-Api-Secret-Token', 'winter-test')->postJson('/telegram/webhook', [
            'update_id' => 1000 + $index,
            'widget_query' => ['id' => 'q'.($index + 1), 'chat' => ['id' => 101, 'type' => 'private'], 'message_id' => 56, 'from' => ['id' => $from], 'data' => 'open:7'],
        ])->assertSuccessful();
    }
});

test('полный декабрь с 31 подарком укладывается в лимиты нативного сообщения', function () {
    $calendar = widgetCalendar();
    for ($day = 1; $day <= 31; $day++) {
        DB::table('gift_days')->updateOrInsert(['calendar_id' => $calendar, 'date' => sprintf('2026-12-%02d', $day)], ['text' => str_repeat('✨', 200)]);
    }
    $message = app(SendWidgetAction::class)->execute(101, $calendar);
    $html = WidgetMessage::query()->findOrFail($message)->html;
    assert(is_string($html));
    expect(strlen(gzcompress($html, 9) ?: throw new RuntimeException('Ошибка zlib')))->toBeLessThanOrEqual(4096);
});

test('после указанного Telegram интервала разрешённая повторная попытка доставляет одну карточку', function () {
    widgetCalendar();
    $gateway = mock(NutgramGateway::class);
    $gateway->shouldReceive('sendWidget')->once()->ordered()->andThrow(new TelegramException('Too many', 429, parameters: ['retry_after' => 37]));
    $gateway->shouldReceive('sendWidget')->once()->ordered()->andReturn(1010);
    app(DispatchMorningRemindersAction::class)->execute();
    widgetTime('2026-12-07 10:00:36');
    app(DispatchMorningRemindersAction::class)->execute();
    expect(DB::table('widget_messages')->count())->toBe(0);
    widgetTime('2026-12-07 10:00:37');
    app(DispatchMorningRemindersAction::class)->execute();
    app(DispatchMorningRemindersAction::class)->execute();
    expect(DB::table('widget_messages')->count())->toBe(1)
        ->and(DB::table('widget_messages')->value('telegram_message_id'))->toBe(1010);
});

test('Nutgram формирует запросы для нативного виджета, кнопки Mini App и ответа на открытие', function () {
    $sdk = Nutgram::fake();
    app()->instance(Nutgram::class, $sdk);
    $gateway = new class extends NutgramGateway
    {
        public function simulated(): bool
        {
            return false;
        }
    };
    $message = ['message_id' => 7, 'date' => now()->timestamp, 'chat' => ['id' => 101, 'type' => 'private']];
    $sdk->willReceive($message)->willReceive($message)->willReceive(true);
    expect($gateway->sendWidget(101, '<b>✦</b>', 'Доброе утро'))->toBe(7);
    $gateway->welcome(101);
    $gateway->answer('q1', 'записка');
    $sdk->assertReply('sendWidget', ['html' => '<b>✦</b>', 'chat_id' => 101, 'caption' => 'Доброе утро', 'allow_queries' => true], 0)
        ->assertReply('sendWidget', ['chat_id' => 101, 'caption' => '', 'allow_queries' => false], 1)
        ->assertReply('answerWidgetQuery', ['widget_query_id' => 'q1', 'data' => 'записка'], 2);
});

test('рабочее задание перепланируется через retry_after с сохранением места в очереди', function () {
    widgetCalendar();
    Queue::fake();
    app(DispatchMorningRemindersAction::class)->execute();
    $id = MorningDelivery::query()->sole()->id;
    mock(NutgramGateway::class)->shouldReceive('sendWidget')->once()->andThrow(new TelegramException('Too many', 429, parameters: ['retry_after' => 9]));
    $job = new SendMorningGift($id);
    $workerJob = Mockery::mock(DatabaseJob::class);
    $workerJob->shouldReceive('release')->once()->with(9);
    assert($workerJob instanceof DatabaseJob);
    $job->setJob($workerJob);
    $job->handle(app(DeliverMorningGiftAction::class));
    expect(DB::table('morning_deliveries')->value('queued_at'))->not->toBeNull();
});

test('повтор стартового обновления после подтверждённого отказа продолжает вручение', function () {
    config(['services.telegram.webhook_secret' => 'winter-test', 'queue.default' => 'database']);
    widgetCalendar();
    $gateway = mock(NutgramGateway::class);
    $gateway->shouldReceive('welcome')->once()->with(101);
    $gateway->shouldReceive('sendWidget')->once()->ordered()->andThrow(new TelegramException('Too Many Requests', 429, parameters: ['retry_after' => 1]));
    $gateway->shouldReceive('sendWidget')->once()->ordered()->andReturn(301);
    $command = ['update_id' => 765, 'message' => ['chat' => ['id' => 101, 'type' => 'private'], 'from' => ['id' => 101, 'username' => 'winter101'], 'text' => '/start']];
    withHeader('X-Telegram-Bot-Api-Secret-Token', 'winter-test');
    postJson('/telegram/webhook', $command)->assertServerError();
    widgetTime('2026-12-07 10:00:02');
    postJson('/telegram/webhook', $command)->assertSuccessful();
    expect(DB::table('widget_messages')->where('telegram_message_id', 301)->count())->toBe(1);
});

test('webhook проверяет структуру запроса виджета до вызова use case', function () {
    config(['services.telegram.webhook_secret' => 'winter-test']);
    withHeader('X-Telegram-Bot-Api-Secret-Token', 'winter-test')->postJson('/telegram/webhook', [
        'update_id' => 89, 'widget_query' => ['id' => 'q', 'from' => ['id' => 'invalid'], 'data' => ['unexpected']],
    ])->assertUnprocessable()->assertJsonValidationErrors(['widget_query.from.id', 'widget_query.data']);
});

test('превью выдаёт типизированную ленту и HTML только владельцу сообщения', function () {
    $calendar = widgetCalendar();
    $message = app(SendWidgetAction::class)->execute(101, $calendar);
    $inbox = app(WidgetInboxService::class);
    expect($inbox->forViewer(101)->sole())->toBeInstanceOf(WidgetInboxItemDTO::class)
        ->and($inbox->forViewer(null))->toBeEmpty()
        ->and($inbox->previewHtml(101, $message))->toContain('TelegramWidget');
    expect(fn () => $inbox->previewHtml(102, $message))->toThrow(HttpException::class);
});

test('планировщик восстанавливает постановку задания после потери процесса при коммите', function (): void {
    widgetCalendar();
    Queue::fake();
    app(DispatchMorningRemindersAction::class)->execute();
    $delivery = MorningDelivery::query()->sole();
    $delivery->update(['queued_at' => now()->subMinutes(6)]);
    app(DispatchMorningRemindersAction::class)->execute();
    Queue::assertPushed(SendMorningGift::class, 2);
    expect(MorningDelivery::query()->count())->toBe(1);
});

test('фабрики сообщений и доставок создают связанные с получателем календари', function (): void {
    $message = WidgetMessage::factory()->create();
    $delivery = MorningDelivery::factory()->create();
    expect($message->recipient_id)->toBe($message->calendar?->recipient_id)
        ->and($delivery->recipient_id)->toBe($delivery->calendar?->recipient_id);
});

test('сбой подготовки HTML до обращения в Telegram оставляет доставку доступной для повтора', function (): void {
    widgetCalendar();
    Queue::fake();
    app(DispatchMorningRemindersAction::class)->execute();
    $delivery = MorningDelivery::query()->sole();
    View::composer('widgets.morning', static fn () => throw new RuntimeException('Временный сбой подготовки шаблона'));
    mock(NutgramGateway::class)->shouldReceive('sendWidget')->once()->andReturn(551);
    $action = app(DeliverMorningGiftAction::class);
    expect(fn () => $action->execute($delivery->id, true))->toThrow(RuntimeException::class);
    expect($delivery->refresh()->status)->toBe('pending');
    Event::forget('composing: widgets.morning');
    $action->execute($delivery->id, true);
    expect($delivery->refresh()->status)->toBe('sent')
        ->and(WidgetMessage::query()->sole()->telegram_message_id)->toBe(551);
});

test('общая пауза 429 сохраняет самый поздний срок от разных воркеров', function (): void {
    $limiter = app(MorningRateLimiter::class);
    $limiter->pauseUntil(now()->toImmutable()->addSeconds(60));
    widgetTime('2026-12-07 10:00:01');
    $limiter->pauseUntil(now()->toImmutable()->addSeconds(3));
    expect($limiter->reserveSlot())->toBe(59);
    widgetTime('2026-12-07 10:01:00');
    expect($limiter->reserveSlot())->toBe(0);
});

test('планировщик различает живое и потерянное задание в очереди PostgreSQL', function (bool $legacy): void {
    config(['queue.default' => 'database']);
    widgetCalendar();
    $dispatch = app(DispatchMorningRemindersAction::class);
    expect($dispatch->execute())->toBe(1);
    $job = DB::table('jobs')->sole();
    assert(is_string($job->payload));
    if ($legacy) {
        $payload = widgetReply($job->payload);
        $payload['displayName'] = SendMorningGift::class;
        DB::table('jobs')->where('id', $job->id)->update(['payload' => json_encode($payload, JSON_THROW_ON_ERROR)]);
    }
    widgetTime('2026-12-07 10:07:00');
    expect($dispatch->execute())->toBe(0)
        ->and(DB::table('jobs')->count())->toBe(1);
    DB::table('jobs')->where('id', $job->id)->delete();
    expect($dispatch->execute())->toBe(1)
        ->and(DB::table('jobs')->count())->toBe(1)
        ->and(MorningDelivery::query()->count())->toBe(1);
})->with(['текущее' => false, 'до обновления' => true]);

test('сбой фиксации принятой Telegram карточки откатывает локальную привязку и исключает дубль', function (): void {
    widgetCalendar();
    Queue::fake();
    $scheduler = app(DispatchMorningRemindersAction::class);
    $scheduler->execute();
    $delivery = MorningDelivery::query()->sole();
    DB::listen(static function (QueryExecuted $query): void {
        if (str_starts_with($query->sql, 'update "morning_deliveries"') && in_array('sent', $query->bindings, true)) {
            throw new RuntimeException('Сбой локальной транзакции подтверждения');
        }
    });
    mock(NutgramGateway::class)->shouldReceive('sendWidget')->once()->andReturn(557);
    $send = app(DeliverMorningGiftAction::class);
    expect(fn () => $send->execute($delivery->id, true))->toThrow(RuntimeException::class);
    expect(WidgetMessage::query()->count())->toBe(0)
        ->and($delivery->refresh()->status)->toBe('sending');
    widgetTime('2026-12-07 10:06:00');
    $scheduler->execute();
    $send->execute($delivery->id, true);
    expect($delivery->refresh()->status)->toBe('unknown');
});

test('поздний подтверждённый ответ уточняет исход после проверки зависшей отправки', function (): void {
    widgetCalendar();
    Queue::fake();
    $dispatch = app(DispatchMorningRemindersAction::class);
    $dispatch->execute();
    $delivery = MorningDelivery::query()->sole();
    mock(NutgramGateway::class)->shouldReceive('sendWidget')->once()->andReturnUsing(static function () use ($dispatch): int {
        widgetTime('2026-12-07 10:06:00');
        $dispatch->execute();

        return 558;
    });
    app(DeliverMorningGiftAction::class)->execute($delivery->id, true);
    expect($delivery->refresh()->status)->toBe('sent')
        ->and($delivery->published_message_id)->toBe(WidgetMessage::query()->sole()->id);
});
