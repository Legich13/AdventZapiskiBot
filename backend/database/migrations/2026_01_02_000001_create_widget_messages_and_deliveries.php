<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            $table->timestamp('bot_blocked_at')->nullable();
        });
        Schema::create('widget_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_id')->constrained();
            $table->foreignId('recipient_id')->constrained('telegram_users');
            $table->bigInteger('telegram_message_id')->nullable();
            $table->date('gift_date')->nullable();
            $table->text('html')->nullable(); // Для браузерной копии в локальном чате.
            $table->string('caption');
            $table->timestamp('created_at');
            $table->unique(['recipient_id', 'telegram_message_id']);
        });
        Schema::create('morning_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_id')->constrained();
            $table->foreignId('recipient_id')->constrained('telegram_users');
            $table->date('gift_date');
            $table->string('status', 16)->default('pending');
            $table->timestamp('available_at');
            $table->timestamp('queued_at')->nullable();
            $table->foreignId('published_message_id')->nullable()->constrained('widget_messages');
            $table->timestamps();
            $table->unique(['recipient_id', 'gift_date']);
            $table->index(['gift_date', 'status', 'available_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('morning_deliveries');
        Schema::dropIfExists('widget_messages');
        Schema::table('telegram_users', fn (Blueprint $table) => $table->dropColumn('bot_blocked_at'));
    }
};
