<?php

declare(strict_types=1);

use App\Calendar\Actions\ClaimCalendarsAction;
use App\Calendar\Actions\OpenGiftAction;
use App\Calendar\Actions\PublishCalendarAction;
use App\Models\Calendar;
use App\Models\GiftDay;
use App\Models\TelegramUser;
use Dedoc\Scramble\Generator;
use Dedoc\Scramble\Scramble;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\withSession;

use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-12-06 20:59:00 UTC');
    config(['services.telegram.token' => 'test-telegram-token']);
});

afterEach(fn () => Carbon::setTestNow());

/** @return array{month:string,target:string,username:?string,days:list<array{date:string,text:string}>} */
function calendarPayload(string $target = 'username'): array
{
    return [
        'month' => '2026-12',
        'target' => $target,
        'username' => $target === 'username' ? '@Receiver' : null,
        'days' => [
            ['date' => '2026-12-07', 'text' => 'Под ёлкой ✨'],
            ['date' => '2026-12-31', 'text' => 'Пусть сбудется мечта'],
        ],
    ];
}

function calendarResponseId(mixed $value): int
{
    if (! is_int($value)) {
        throw new UnexpectedValueException('Ответ редактора должен содержать числовой ID календаря.');
    }

    return $value;
}

function calendarInitData(?string $username = 'Oleg', int $offset = 0): string
{
    $fields = [
        'auth_date' => (string) (now()->getTimestamp() + $offset),
        'query_id' => 'example',
        'signature' => 'telegram-signature',
        'user' => json_encode(['id' => 41, 'username' => $username], JSON_THROW_ON_ERROR),
    ];
    $key = hash_hmac('sha256', 'test-telegram-token', 'WebAppData', true);
    $fields['hash'] = hash_hmac('sha256', implode("\n", array_map(
        fn ($key, $value) => "$key=$value", array_keys($fields), $fields,
    )), $key);

    return http_build_query($fields);
}

test('редактор принимает подписанный вход и отклоняет изменённый или просроченный', function () {
    postJson('/app/calendars', calendarPayload())->assertUnauthorized();
    config(['services.telegram.token' => null]);
    postJson('/app/session', ['initData' => calendarInitData()])->assertUnauthorized();
    config(['services.telegram.token' => 'test-telegram-token']);
    postJson('/app/session', ['initData' => calendarInitData(offset: -7200)])->assertUnauthorized();
    postJson('/app/session', ['initData' => str_replace('Oleg', 'Ivan', calendarInitData())])->assertUnauthorized();
    postJson('/app/session', ['initData' => calendarInitData()])->assertNoContent()->assertSessionHas('telegram_id', 41);
    expect(TelegramUser::findOrFail(41)->username)->toBe('oleg');
});

test('черновик можно опубликовать и вручить по текущему нику только одному пользователю', function () {
    $calendars = app(ClaimCalendarsAction::class);
    $calendars->execute(41, 'Author');
    $id = calendarResponseId(withSession(['telegram_id' => 41])->postJson('/app/calendars', calendarPayload())->assertSuccessful()->json('id'));
    expect($calendars->execute(42, 'Receiver'))->toHaveCount(0);
    postJson("/app/calendars/$id/publish")->assertSuccessful();
    expect($calendars->execute(42, 'RECEIVER'))->toBe([$id]);
    expect($calendars->execute(42, 'NewName'))->toBe([$id]);
    expect($calendars->execute(43, 'Receiver'))->toHaveCount(0);

    expect(fn () => app(OpenGiftAction::class)->execute(42, $id, '2026-12-07'))->toThrow(HttpException::class);
    Carbon::setTestNow('2026-12-06 21:00:00 UTC');
    expect(get_object_vars(app(OpenGiftAction::class)->execute(42, $id, '2026-12-07')))->toMatchArray(['text' => 'Под ёлкой ✨', 'first' => true]);
    expect(get_object_vars(app(OpenGiftAction::class)->execute(42, $id, '2026-12-07')))->toMatchArray(['text' => 'Под ёлкой ✨', 'first' => false]);
    expect(fn () => app(OpenGiftAction::class)->execute(43, $id, '2026-12-07'))->toThrow(HttpException::class);

    withSession(['telegram_id' => 43])->putJson("/app/calendars/$id", calendarPayload())->assertForbidden();
    get('/app?calendar='.$id)->assertForbidden();
});

test('черновик с пустым выбранным днём сохраняется до завершения, конфликт публикации сохраняет записи', function () {
    $calendars = app(ClaimCalendarsAction::class);
    $calendars->execute(41, 'Author');
    $calendars->execute(45, 'Other');
    $payload = calendarPayload();
    $payload['days'][0]['text'] = '';
    $id = calendarResponseId(withSession(['telegram_id' => 41])->postJson('/app/calendars', $payload)->assertSuccessful()->json('id'));
    postJson("/app/calendars/$id/publish")->assertUnprocessable();
    putJson("/app/calendars/$id", calendarPayload())->assertSuccessful();
    $other = calendarResponseId(withSession(['telegram_id' => 45])->postJson('/app/calendars', calendarPayload())->assertSuccessful()->json('id'));
    postJson("/app/calendars/$other/publish")->assertSuccessful();
    withSession(['telegram_id' => 41])->postJson("/app/calendars/$id/publish")->assertConflict();
    expect(Calendar::findOrFail($id)->published_at)->toBeNull();
    expect(GiftDay::where('calendar_id', $id)->count())->toBe(2);
});

test('для себя нужен только идентификатор, можно править будущий текст, прошедшие даты защищены', function () {
    $calendars = app(ClaimCalendarsAction::class);
    $calendars->execute(41, null);
    $payload = calendarPayload('self');
    $id = calendarResponseId(withSession(['telegram_id' => 41])->postJson('/app/calendars', $payload)->assertSuccessful()->json('id'));
    postJson("/app/calendars/$id/publish")->assertSuccessful();
    expect($calendars->execute(41, null))->toBe([$id]);
    Carbon::setTestNow('2026-12-07 07:01:00 UTC');
    $payload['days'][1]['text'] = 'Новый сюрприз!';
    putJson("/app/calendars/$id", $payload)->assertSuccessful();
    $payload['days'][0]['text'] = 'Изменённая история';
    putJson("/app/calendars/$id", $payload)->assertUnprocessable();
    expect(app(OpenGiftAction::class)->execute(41, $id, '2026-12-07')->text)->toBe('Под ёлкой ✨');
});

test('редактор проверяет високосную дату и байты готового ответа', function () {
    app(ClaimCalendarsAction::class)->execute(41, null);
    $payload = calendarPayload('self');
    $payload['month'] = '2028-02';
    $payload['days'] = [['date' => '2028-02-29', 'text' => 'Сюрприз!']];
    withSession(['telegram_id' => 41])->postJson('/app/calendars', $payload)->assertSuccessful();
    $payload['days'][0]['date'] = '2027-02-29';
    postJson('/app/calendars', $payload)->assertUnprocessable();
    $payload['days'][0] = ['date' => '2028-03-01', 'text' => 'Ой'];
    postJson('/app/calendars', $payload)->assertUnprocessable();
    $payload['days'][0] = ['date' => '2028-02-29', 'text' => str_repeat('🎁', 256)];
    postJson('/app/calendars', $payload)->assertUnprocessable();
});

test('смена ника при коллизии месяца возвращает второму автору редактируемый черновик', function () {
    $calendars = app(ClaimCalendarsAction::class);
    $calendars->execute(41, 'Author');
    $one = calendarResponseId(withSession(['telegram_id' => 41])->postJson('/app/calendars', calendarPayload())->json('id'));
    postJson("/app/calendars/$one/publish")->assertSuccessful();
    $calendars->execute(42, 'Receiver');
    $payload = calendarPayload();
    $payload['username'] = 'NewName';
    $two = calendarResponseId(postJson('/app/calendars', $payload)->json('id'));
    postJson("/app/calendars/$two/publish")->assertSuccessful();
    expect($calendars->execute(42, 'NewName'))->toBe([$one]);
    expect(Calendar::findOrFail($two)->published_at)->toBeNull();
});

test('после публикации можно исправить ожидающий ник, после вручения получатель остаётся прежним', function () {
    $calendars = app(ClaimCalendarsAction::class);
    $calendars->execute(41, 'Author');
    $payload = calendarPayload();
    $id = calendarResponseId(withSession(['telegram_id' => 41])->postJson('/app/calendars', $payload)->json('id'));
    postJson("/app/calendars/$id/publish")->assertSuccessful();
    $payload['username'] = '@CorrectName';
    putJson("/app/calendars/$id", $payload)->assertSuccessful();
    expect($calendars->execute(42, 'Receiver'))->toHaveCount(0);
    expect($calendars->execute(43, 'CorrectName'))->toBe([$id]);
    $payload['username'] = '@AnotherName';
    putJson("/app/calendars/$id", $payload)->assertUnprocessable();
});

test('лимит публикаций относится к автору и выдаёт срок повтора', function () {
    $calendars = app(ClaimCalendarsAction::class);
    $calendars->execute(41, 'Author');
    $calendars->execute(42, 'Other');
    foreach ([41, 42] as $authorId) {
        $id = calendarResponseId(withSession(['telegram_id' => $authorId])->postJson('/app/calendars', calendarPayload('self'))->json('id'));
        postJson("/app/calendars/$id/publish")->assertSuccessful();
        if ($authorId === 41) {
            foreach (range(1, 5) as $_) {
                postJson("/app/calendars/$id/publish")->assertSuccessful();
            }
            postJson("/app/calendars/$id/publish")->assertTooManyRequests()->assertHeader('Retry-After');
        }
    }
});

test('опубликованный календарь на известный ник резервирует месяц и при подарке себе', function () {
    $calendars = app(ClaimCalendarsAction::class);
    $calendars->execute(41, 'Author');
    $calendars->execute(42, 'Receiver');
    $pending = calendarResponseId(withSession(['telegram_id' => 41])->postJson('/app/calendars', calendarPayload())->json('id'));
    postJson("/app/calendars/$pending/publish")->assertSuccessful();
    $self = calendarResponseId(withSession(['telegram_id' => 42])->postJson('/app/calendars', calendarPayload('self'))->json('id'));
    postJson("/app/calendars/$self/publish")->assertConflict();
    expect($calendars->execute(42, 'Receiver'))->toBe([$pending]);
});

/** @return Closure():int */
function calendarParallelPublication(int $authorId, int $id): Closure
{
    return static function () use ($authorId, $id) {
        try {
            app(PublishCalendarAction::class)->execute($authorId, $id);

            return 200;
        } catch (HttpException $e) {
            return $e->getStatusCode();
        }
    };
}

/** @return Closure():bool */
function calendarParallelOpen(int $id): Closure
{
    return static function () use ($id) {
        Carbon::setTestNow('2026-12-07 08:00:00 UTC');

        return app(OpenGiftAction::class)->execute(81043, $id, '2026-12-07')->first;
    };
}

test('параллельные публикации и открытия в отдельных соединениях PostgreSQL дают один результат', function () {
    $calendars = app(ClaimCalendarsAction::class);
    $ids = [];
    foreach ([81041, 81042] as $authorId) {
        $calendars->execute($authorId, null);
        $data = calendarPayload();
        $data['username'] = 'ParallelReceiver';
        $ids[$authorId] = calendarResponseId(withSession(['telegram_id' => $authorId])->postJson('/app/calendars', $data)->json('id'));
    }

    // Дочерние процессы должны видеть сохранённые черновики через отдельные соединения.
    $db = DB::connection();
    $db->commit();
    try {
        $tasks = [];
        foreach ($ids as $authorId => $id) {
            $tasks[] = calendarParallelPublication($authorId, $id);
        }
        $statuses = Concurrency::run($tasks);
        sort($statuses);
        expect($statuses)->toBe([200, 409]);
        $calendar = $calendars->execute(81043, 'ParallelReceiver')[0];
        $id = $calendar;
        $open = calendarParallelOpen($id);
        $first = Concurrency::run([$open, $open]);
        sort($first);
        expect($first)->toBe([false, true]);
    } finally {
        Calendar::whereIn('id', $ids)->delete();
        TelegramUser::whereIn('id', [81041, 81042, 81043])->delete();
        $db->beginTransaction();
    }
});

test('фабрики воспроизводят структуру связей Telegram и календаря', function () {
    $author = TelegramUser::factory()->create();
    $calendar = Calendar::factory()->for($author, 'author')->create();
    $day = GiftDay::factory()->for($calendar)->create();

    expect($day->calendar->author->is($author))->toBeTrue()
        ->and($calendar->recipient_id)->toBeNull();
});

test('OpenAPI различает создание и обновление и описывает ID календаря целым числом', function (): void {
    $result = app(Generator::class)->generate(Scramble::getGeneratorConfig(Scramble::DEFAULT_API));
    $spec = $result->spec();
    $reference = '#/components/schemas/CalendarIdResource';

    expect(data_get($spec, 'paths./app/calendars.post.responses.201.content.application/json.schema.$ref'))->toBe($reference)
        ->and(data_get($spec, 'paths./app/calendars.post.responses.200'))->toBeNull()
        ->and(data_get($spec, 'paths./app/calendars/{calendar}.put.responses.200.content.application/json.schema.$ref'))->toBe($reference)
        ->and(data_get($spec, 'paths./app/calendars/{calendar}.put.parameters.0.schema.type'))->toBe('integer')
        ->and(data_get($spec, 'components.schemas.CalendarIdResource.required'))->toBe(['id'])
        ->and(data_get($spec, 'components.schemas.CalendarIdResource.properties.id.type'))->toBe('integer')
        ->and($result->diagnostics()->contains(fn ($issue): bool => $issue->code() === 'JR001'))->toBeFalse();
})->group('openapi');
