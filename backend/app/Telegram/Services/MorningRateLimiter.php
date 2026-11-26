<?php

declare(strict_types=1);

namespace App\Telegram\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

final class MorningRateLimiter
{
    /** Возвращает задержку перед вызовом Bot API на общем лимите воркеров. */
    public function reserveSlot(): int
    {
        $lock = Cache::lock('morning-rate-lock', 2);
        if (! $lock->get()) {
            return 1;
        }
        try {
            $until = Cache::get('morning-retry-until');
            $timestamp = now()->getTimestamp();
            if (is_int($until) && $until > $timestamp) {
                return $until - $timestamp;
            }

            return RateLimiter::attempt('morning-outbound', 20, fn (): bool => true, 1) ? 0 : 1;
        } finally {
            $lock->release();
        }
    }

    public function pauseUntil(CarbonImmutable $until): void
    {
        Cache::lock('morning-rate-lock', 2)->block(5, static function () use ($until): void {
            $current = Cache::get('morning-retry-until');
            if (! is_int($current) || $current < $until->timestamp) {
                Cache::put('morning-retry-until', $until->timestamp, $until);
            }
        });
    }
}
