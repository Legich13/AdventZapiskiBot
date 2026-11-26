<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_start_updates', function (Blueprint $table): void {
            $table->bigInteger('update_id')->primary();
            $table->bigInteger('viewer_id');
            $table->jsonb('calendar_ids')->nullable();
        });
        Schema::create('telegram_start_messages', function (Blueprint $table): void {
            $table->bigInteger('update_id');
            $table->integer('position');
            $table->primary(['update_id', 'position']);
            $table->foreign('update_id')->references('update_id')->on('telegram_start_updates')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_start_messages');
        Schema::dropIfExists('telegram_start_updates');
    }
};
