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
        Schema::create('telegram_users', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->string('username', 32)->nullable()->unique();
            $table->timestampsTz();
        });
        Schema::create('calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('telegram_users');
            $table->foreignId('recipient_id')->nullable()->constrained('telegram_users');
            $table->string('recipient_username', 32)->nullable();
            $table->boolean('is_self')->default(false);
            $table->date('month');
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('claimed_at')->nullable();
            $table->timestampTz('claim_conflict_at')->nullable();
            $table->timestampsTz();
            $table->index('author_id');
        });
        DB::statement('CREATE UNIQUE INDEX calendars_claimed_month ON calendars (recipient_id, month) WHERE published_at IS NOT NULL AND recipient_id IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX calendars_pending_month ON calendars (recipient_username, month) WHERE published_at IS NOT NULL AND recipient_id IS NULL');
        Schema::create('gift_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->text('text');
            $table->timestampTz('opened_at')->nullable();
            $table->timestampsTz();
            $table->unique(['calendar_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_days');
        Schema::dropIfExists('calendars');
        Schema::dropIfExists('telegram_users');
    }
};
