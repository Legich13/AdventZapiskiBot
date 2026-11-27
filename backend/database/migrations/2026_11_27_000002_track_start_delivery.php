<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE telegram_start_updates
              ADD COLUMN IF NOT EXISTS retry_at timestamp(0) without time zone,
              ADD COLUMN IF NOT EXISTS outcome varchar(16),
              ADD COLUMN IF NOT EXISTS telegram_error_code integer
        SQL);
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS telegram_cover_waits (
                calendar_id bigint PRIMARY KEY REFERENCES calendars(id) ON DELETE CASCADE,
                gift_date date NOT NULL,
                retry_on_start boolean NOT NULL DEFAULT false
            )
        SQL);
    }

    public function down(): void
    {
        if (DB::table('telegram_cover_waits')->exists() || DB::table('telegram_start_updates')->whereNotNull('outcome')->exists()) {
            throw new RuntimeException('Откат схемы удалит учёт отправки. Откатывайте код с сохранением журнала.');
        }
        DB::statement('DROP TABLE telegram_cover_waits');
        DB::statement('ALTER TABLE telegram_start_updates DROP COLUMN retry_at, DROP COLUMN outcome, DROP COLUMN telegram_error_code');
    }
};
