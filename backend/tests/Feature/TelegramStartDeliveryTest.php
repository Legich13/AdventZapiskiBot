<?php

declare(strict_types=1);

use App\Models\Calendar;
use App\Models\GiftDay;
use App\Models\TelegramUser;
use App\Telegram\Actions\HandleTelegramUpdateAction;
use App\Telegram\DTO\BotMessageDTO;
use App\Telegram\DTO\StartUpdateDTO;
use App\Telegram\Infrastructure\NutgramGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\mock;

use SergiX44\Nutgram\Telegram\Exceptions\TelegramException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow('2026-12-07 07:00:00 UTC');
    config(['queue.default' => 'database']);
});

afterEach(fn () => Carbon::setTestNow());

test('новый старт повторяет только явно отклонённую открытку и различает отказ блокировку и неизвестный ответ', function (int $code, string $status): void {
    $recipient = TelegramUser::factory()->create(['username' => 'returned_reader']);
    $calendar = Calendar::factory()->create(['recipient_username' => 'returned_reader', 'is_self' => false, 'published_at' => now()]);
    GiftDay::factory()->create(['calendar_id' => $calendar->id, 'date' => '2026-12-07']);
    $gateway = mock(NutgramGateway::class);
    $failure = $code === 0 ? new RuntimeException('Потерян ответ') : new TelegramException('Отклонено', $code);
    $gateway->shouldReceive('send')->once()->ordered()->andThrow($failure);
    $gateway->shouldReceive('send')->once()->ordered()->with($recipient->id, Mockery::on(
        static fn (BotMessageDTO $m): bool => ($m->photo !== null) === ($status !== 'unknown'),
    ))->andReturn(9372);
    $handler = app(HandleTelegramUpdateAction::class);
    $first = new StartUpdateDTO(9371, $recipient->id, 'returned_reader');
    try {
        $handler->execute($first);
    } catch (RuntimeException) {
        // При обрыве транспорта вызывающий код может получить исключение.
    }
    $handler->execute($first);
    expect(DB::table('telegram_start_updates')->where('update_id', 9371)->first()->outcome ?? null)->toBe($status)
        ->and($recipient->refresh()->bot_blocked_at !== null)->toBe($code === 403);
    $handler->execute(new StartUpdateDTO(9372, $recipient->id, 'returned_reader'));
    expect($recipient->refresh()->bot_blocked_at)->toBeNull()
        ->and(DB::table('jobs')->count())->toBe($status === 'unknown' ? 0 : 1)
        ->and(DB::table('telegram_start_updates')->where('update_id', 9372)->first()->outcome ?? null)->toBe('sent');
})->with(['заблокирован' => [403, 'blocked'], 'запрос отклонён' => [400, 'failed'], 'ответ потерян' => [0, 'unknown'], 'сбой сервера' => [502, 'unknown']]);
