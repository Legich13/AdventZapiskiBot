<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('widget_messages', function (Blueprint $table): void {
            $table->foreignId('calendar_id')->nullable()->change();
            $table->string('button_text')->nullable();
            $table->string('app_path')->nullable();
            $table->string('photo')->nullable();
        });
        Schema::table('telegram_start_updates', function (Blueprint $table): void {
            $table->boolean('create_calendar')->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('widget_messages')->whereNotNull('app_path')->exists()) {
            throw new RuntimeException('Откат схемы удалит историю отправленных кнопок. Сохраните новую схему при откате приложения.');
        }
        Schema::table('telegram_start_updates', function (Blueprint $table): void {
            $table->dropColumn('create_calendar');
        });
        Schema::table('widget_messages', function (Blueprint $table): void {
            $table->dropColumn(['button_text', 'app_path', 'photo']);
            $table->foreignId('calendar_id')->nullable(false)->change();
        });
    }
};
