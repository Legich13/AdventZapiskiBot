<?php

declare(strict_types=1);

use App\Calendar\DTO\CalendarInputDTO;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Постоянный ключ отличает наш подарок от другого календаря тех же людей при повторе.
    private const int CALENDAR_ID = 412_796_305_841_753;

    private const int AUTHOR_ID = 162_151_358;

    private const int RECIPIENT_ID = 160_921_667;

    private const string RECIPIENT_USERNAME = 'evergreensun';

    private const string MONTH = '2026-12-01';

    #[Override]
    public function shouldRun(): bool
    {
        return app()->isProduction();
    }

    public function up(): void
    {
        // PostgreSQL выполняет эту миграцию в транзакции. На время проверки имён и месяца
        // исключаем одновременную регистрацию, создание и публикацию других календарей.
        DB::statement('LOCK TABLE telegram_users, calendars IN SHARE ROW EXCLUSIVE MODE');
        $previous = DB::table('calendars')->where('id', self::CALENDAR_ID);
        if ($previous->exists()) {
            if ($previous->where('author_id', self::AUTHOR_ID)->where('recipient_id', self::RECIPIENT_ID)
                ->where('month', self::MONTH)->whereNotNull('published_at')->where('is_self', false)->exists()) {
                return;
            }

            throw new RuntimeException('Идентификатор декабрьского подарка уже занят другим календарём.');
        }

        $this->ensureProfile(self::AUTHOR_ID, 'legich13');
        $this->ensureProfile(self::RECIPIENT_ID, self::RECIPIENT_USERNAME);
        $currentName = DB::table('telegram_users')->where('id', self::RECIPIENT_ID)->value('username');
        $address = is_string($currentName) ? $currentName : self::RECIPIENT_USERNAME;
        $pendingNames = is_string($currentName) ? [$currentName] : [];
        $originalNameTaken = DB::table('telegram_users')->where('username', self::RECIPIENT_USERNAME)
            ->where('id', '<>', self::RECIPIENT_ID)->exists();
        if (! $originalNameTaken && $currentName !== self::RECIPIENT_USERNAME) {
            $pendingNames[] = self::RECIPIENT_USERNAME;
        }
        $occupied = DB::table('calendars')->where('month', self::MONTH)->whereNotNull('published_at')
            ->where(function (Builder $query) use ($pendingNames): void {
                $query->where('recipient_id', self::RECIPIENT_ID)->orWhere(function (Builder $waiting) use ($pendingNames): void {
                    $waiting->whereNull('recipient_id')->whereIn('recipient_username', $pendingNames);
                });
            })->exists();
        if ($occupied) {
            throw new RuntimeException('На декабрь 2026 уже опубликован календарь для @EvergreenSun. Проверьте существующий подарок перед повтором миграции.');
        }

        $now = now();
        DB::table('calendars')->insert([
            'id' => self::CALENDAR_ID, 'author_id' => self::AUTHOR_ID, 'recipient_id' => self::RECIPIENT_ID,
            'recipient_username' => $address, 'is_self' => false, 'month' => self::MONTH,
            'published_at' => $now, 'created_at' => $now, 'updated_at' => $now,
        ]);
        /** @var array<string, string> $notes */
        $notes = require database_path('data/kazan_december_2026.php');
        $rows = [];
        foreach (range(1, 31) as $day) {
            $date = sprintf('2026-12-%02d', $day);
            $text = $notes[$date] ?? '';
            if (trim($text) === '' || ! CalendarInputDTO::giftFits($text)) {
                throw new RuntimeException("Проверьте наличие и размер записки за {$date}.");
            }
            $rows[] = ['calendar_id' => self::CALENDAR_ID, 'date' => $date, 'text' => $text, 'created_at' => $now, 'updated_at' => $now];
        }
        DB::table('gift_days')->insert($rows);
    }

    public function down(): void
    {
        // Подарок мог быть вручён и отредактирован. Откат кода сохраняет данные участников.
    }

    private function ensureProfile(int $id, string $username): void
    {
        if (DB::table('telegram_users')->where('id', $id)->exists()) {
            return;
        }

        $nameTaken = DB::table('telegram_users')->where('username', $username)->exists();
        DB::table('telegram_users')->insert([
            'id' => $id, 'username' => $nameTaken ? null : $username, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
};
