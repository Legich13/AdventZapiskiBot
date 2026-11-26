<?php

declare(strict_types=1);

use App\Calendar\DTO\CalendarInputDTO;
use App\Models\Calendar;
use App\Models\TelegramUser;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function migrateKazanCalendar(string $environment = 'production'): void
{
    app()->detectEnvironment(fn (): string => $environment);
    try {
        app(Migrator::class)->run(database_path('migrations'));
    } finally {
        app()->detectEnvironment(fn (): string => 'testing');
    }
}

test('на чистой боевой базе опубликована отдельная записка на каждую дату декабря для Алсу от Олега', function (): void {
    migrateKazanCalendar();

    $calendar = Calendar::query()->sole();
    expect($calendar->author_id)->toBe(162151358)
        ->and($calendar->recipient_id)->toBe(160921667)
        ->and($calendar->author->username)->toBe('legich13')
        ->and($calendar->recipient_username)->toBe('evergreensun')
        ->and($calendar->is_self)->toBeFalse()
        ->and($calendar->month)->toBe('2026-12-01')
        ->and($calendar->published_at)->not->toBeNull()
        ->and($calendar->claimed_at)->toBeNull()
        ->and($calendar->days)->toHaveCount(31);
    foreach ($calendar->days as $index => $day) {
        expect($day->date)->toBe(sprintf('2026-12-%02d', $index + 1))
            ->and(trim($day->text))->not->toBeEmpty()
            ->and(CalendarInputDTO::giftFits($day->text))->toBeTrue()
            ->and($day->opened_at)->toBeNull();
    }
});

test('на локальной и тестовой установке миграция не создаёт подарок и профили', function (string $environment): void {
    migrateKazanCalendar($environment);

    expect(Calendar::count())->toBe(0)->and(TelegramUser::count())->toBe(0);
})->with(['local', 'testing']);

test('повтор миграции сохраняет вручение, открытия, редакторскую правку и текущие ники', function (): void {
    TelegramUser::factory()->create(['id' => 162151358, 'username' => 'newauthor']);
    TelegramUser::factory()->create(['id' => 160921667, 'username' => 'newreader']);
    migrateKazanCalendar();
    $calendar = Calendar::query()->sole();
    $opened = now()->subDay()->startOfSecond();
    $calendar->update(['claimed_at' => $opened]);
    $calendar->days()->whereBetween('date', ['2026-12-01', '2026-12-03'])->update(['opened_at' => $opened]);
    $calendar->days()->where('date', '2026-12-30')->update(['text' => 'Личная правка автора']);

    // При сбое после записи данных запись в журнале мигратора может отсутствовать.
    DB::table('migrations')->where('migration', '2026_11_26_000001_publish_kazan_december_calendar')->delete();
    migrateKazanCalendar();

    expect(Calendar::query()->sole()->id)->toBe($calendar->id)
        ->and($calendar->refresh()->claimed_at?->equalTo($opened))->toBeTrue()
        ->and($calendar->days)->toHaveCount(31)
        ->and($calendar->days()->where('opened_at', $opened)->count())->toBe(3)
        ->and($calendar->days()->where('date', '2026-12-30')->sole()->text)->toBe('Личная правка автора')
        ->and($calendar->author->username)->toBe('newauthor')
        ->and($calendar->recipient?->username)->toBe('newreader');
});

test('при существующем декабрьском подарке миграция сообщает о конфликте и не меняет данные', function (string $target): void {
    $recipient = TelegramUser::factory()->create(['id' => 160921667, 'username' => 'newreader']);
    $existing = Calendar::factory()->create([
        'recipient_id' => $target === 'id' ? $recipient->id : null,
        'recipient_username' => $target === 'id' ? null : $target,
        'is_self' => false,
        'published_at' => now(),
    ]);

    expect(fn () => migrateKazanCalendar())->toThrow(RuntimeException::class, 'На декабрь 2026 уже опубликован календарь')
        ->and(Calendar::query()->sole()->id)->toBe($existing->id)
        ->and(TelegramUser::find(162151358))->toBeNull();
})->with(['id', 'evergreensun', 'newreader']);

test('подарок владельцу прежнего ника остаётся доступен отдельно от персонального календаря Алсу', function (?string $currentName): void {
    $owner = TelegramUser::factory()->create(['username' => 'evergreensun']);
    $ownerOfAuthorName = TelegramUser::factory()->create(['username' => 'legich13']);
    if ($currentName !== null) {
        TelegramUser::factory()->create(['id' => 160921667, 'username' => $currentName]);
    }

    $waiting = Calendar::factory()->create([
        'recipient_username' => 'evergreensun', 'is_self' => false, 'published_at' => now(),
    ]);

    migrateKazanCalendar();

    $calendar = Calendar::where('recipient_id', 160921667)->sole();
    expect($calendar->recipient_username)->toBe($currentName ?? 'evergreensun')
        ->and($calendar->recipient?->username)->toBe($currentName)
        ->and($calendar->author->username)->toBeNull()
        ->and($waiting->refresh()->recipient_id)->toBeNull()
        ->and($waiting->recipient_username)->toBe('evergreensun')
        ->and($owner->refresh()->username)->toBe('evergreensun')
        ->and($ownerOfAuthorName->refresh()->username)->toBe('legich13');
})->with([null, 'newreader']);
