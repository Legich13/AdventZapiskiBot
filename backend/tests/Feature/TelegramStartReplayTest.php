<?php

declare(strict_types=1);

use App\Models\Calendar;
use App\Models\TelegramUser;
use App\Models\WidgetMessage;
use App\Telegram\Actions\HandleTelegramUpdateAction;
use App\Telegram\DTO\StartUpdateDTO;
use App\Telegram\Infrastructure\NutgramGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

use function Pest\Laravel\mock;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeader;

use SergiX44\Nutgram\Telegram\Exceptions\TelegramException;

uses(RefreshDatabase::class);

/** @return Closure():?int */
function concurrentStartReplay(int $updateId, int $viewerId): Closure
{
    return static function () use ($updateId, $viewerId): ?int {
        config(['cache.default' => 'database']);
        Carbon::setTestNow(now()->addMinutes(3));

        return app(HandleTelegramUpdateAction::class)->execute(new StartUpdateDTO($updateId, $viewerId, 'winterbird'));
    };
}

test('повтор старого старта продолжает отправку исходных календарей и сохраняет нового владельца ника', function (): void {
    config(['services.telegram.webhook_secret' => 'replay-test']);
    withHeader('X-Telegram-Bot-Api-Secret-Token', 'replay-test');
    $start = static fn (int $update, int $viewer) => postJson('/telegram/webhook', [
        'update_id' => $update,
        'message' => ['chat' => ['id' => $viewer, 'type' => 'private'], 'from' => ['id' => $viewer, 'username' => 'snowbird'], 'text' => '/start'],
    ]);
    $waiting = static fn (string $month): Calendar => Calendar::factory()->create([
        'recipient_username' => 'snowbird', 'is_self' => false, 'month' => $month, 'published_at' => now(),
    ]);
    $original = $waiting('2026-12-01');
    $gateway = mock(NutgramGateway::class);
    $gateway->shouldReceive('welcome')->once()->with(301);
    $gateway->shouldReceive('welcome')->twice()->with(302);
    $gateway->shouldReceive('sendWidget')->once()->ordered()->andThrow(new TelegramException('Too Many Requests', 429, parameters: ['retry_after' => 2]));
    $gateway->shouldReceive('sendWidget')->andReturn(500, 501, 502, 503);

    $start(1001, 301)->assertStatus(503)->assertHeader('Retry-After', '2');
    expect($original->refresh()->recipient_id)->toBe(301);
    $start(1002, 302)->assertNoContent();
    $forNewOwner = $waiting('2027-01-01');
    Calendar::factory()->create(['recipient_id' => 301, 'month' => '2027-02-01', 'published_at' => now(), 'claimed_at' => now()]);
    TelegramUser::whereKey(301)->update(['bot_blocked_at' => now()]);

    $start(1001, 301)->assertNoContent();
    expect($forNewOwner->refresh()->recipient_id)->toBeNull()
        ->and(TelegramUser::findOrFail(302)->username)->toBe('snowbird')
        ->and(TelegramUser::findOrFail(301)->username)->toBeNull()
        ->and(TelegramUser::findOrFail(301)->bot_blocked_at)->not->toBeNull()
        ->and(WidgetMessage::where('recipient_id', 301)->pluck('calendar_id')->all())->toBe([$original->id]);
    Cache::flush();
    $start(1001, 301)->assertNoContent();
    expect(WidgetMessage::where('recipient_id', 301)->count())->toBe(1);
    $start(1003, 302)->assertNoContent();
    expect($forNewOwner->refresh()->recipient_id)->toBe(302);
});

test('параллельный процесс ждёт незавершённую отправку и два подтверждённых отказа допускают продолжение', function (): void {
    config(['cache.default' => 'database']);
    $command = new StartUpdateDTO(889007, 889003, 'winterbird');
    $gateway = mock(NutgramGateway::class);
    $gateway->shouldReceive('welcome')->once()->ordered()->andReturnUsing(static function () use ($command): never {
        expect(Concurrency::run([concurrentStartReplay($command->updateId, $command->viewerId)]))->toBe([3]);

        throw new TelegramException('Too Many Requests', 429, parameters: ['retry_after' => 4]);
    });
    $gateway->shouldReceive('welcome')->once()->ordered()->andThrow(new TelegramException('Too Many Requests', 429, parameters: ['retry_after' => 8]));
    $gateway->shouldReceive('welcome')->once()->ordered()->andReturnNull();

    // Дочерний процесс подключается отдельно и должен видеть зафиксированный журнал.
    $connection = DB::connection();
    $connection->commit();
    try {
        $handle = app(HandleTelegramUpdateAction::class);
        expect($handle->execute($command))->toBe(4)
            ->and($handle->execute($command))->toBe(8)
            ->and($handle->execute($command))->toBeNull()
            ->and($handle->execute($command))->toBeNull();
    } finally {
        DB::table('telegram_start_updates')->where('update_id', $command->updateId)->delete();
        TelegramUser::whereKey($command->viewerId)->delete();
        RateLimiter::clear('telegram-start-user:'.$command->viewerId);
        $connection->beginTransaction();
    }
});
