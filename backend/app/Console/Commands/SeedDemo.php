<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Studio\DemoProfiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedDemo extends Command
{
    #[\Override]
    protected $signature = 'advent:demo';

    #[\Override]
    protected $description = 'Создать демонстрационные календари в локальном окружении';

    public function handle(): int
    {
        if (! app()->isLocal()) {
            $this->error('Доступно только локально.');

            return self::FAILURE;
        }
        DB::transaction(function () {
            foreach (DemoProfiles::PEOPLE as $id => $username) {
                DB::table('telegram_users')->insertOrIgnore(['id' => $id, 'username' => $username, 'created_at' => now(), 'updated_at' => now()]);
            }
            foreach ([90002, 90003] as $id) {
                if (DB::table('calendars')->where('recipient_id', $id)->where('month', '2026-12-01')->exists()) {
                    continue;
                }
                $calendar = DB::table('calendars')->insertGetId([
                    'author_id' => 90001, 'recipient_id' => $id, 'recipient_username' => DemoProfiles::PEOPLE[$id],
                    'is_self' => false, 'month' => '2026-12-01', 'published_at' => '2026-11-25 07:00:00',
                    'claimed_at' => '2026-12-01 07:00:00', 'created_at' => now(), 'updated_at' => now(),
                ]);
                foreach ([1 => 'Первый снег зовёт на прогулку. Термос с какао беру на себя!', 7 => 'Сегодня мы печём имбирные звёзды. Выбирай самую большую!', 18 => 'Вечером выбираем ёлочное украшение вместе.', 31 => 'Загадай желание под бой часов. Пусть оно сбудется!'] as $day => $note) {
                    DB::table('gift_days')->insert([
                        'calendar_id' => $calendar, 'date' => sprintf('2026-12-%02d', $day), 'text' => $note,
                        'opened_at' => $id === 90003 && $day === 7 ? '2026-12-07 05:00:00' : null,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        });
        $this->info('Мастерская готова. Откройте /dev.');

        return self::SUCCESS;
    }
}
