<?php

declare(strict_types=1);

namespace App\Calendar\Services;

use App\Calendar\DTO\TelegramIdentityDTO;

final class MiniAppIdentityVerifier
{
    public function verify(string $initData): TelegramIdentityDTO
    {
        $token = config('services.telegram.token');
        abort_if(! is_string($token) || $token === '' || strlen($initData) > 16384, 401);
        $fields = [];
        foreach (explode('&', $initData) as $pair) {
            $parts = explode('=', $pair, 2);
            abort_unless(count($parts) === 2, 401);
            $key = urldecode($parts[0]);
            abort_if(! preg_match('/^[A-Za-z0-9_]+$/D', $key) || isset($fields[$key]), 401);
            $fields[$key] = urldecode($parts[1]);
        }
        $hash = $fields['hash'] ?? '';
        unset($fields['hash']);
        ksort($fields, SORT_STRING);
        $lines = [];
        foreach ($fields as $key => $value) {
            $lines[] = $key.'='.$value;
        }
        $secret = hash_hmac('sha256', $token, 'WebAppData', true);
        abort_unless(hash_equals(hash_hmac('sha256', implode("\n", $lines), $secret), $hash), 401);
        $time = filter_var($fields['auth_date'] ?? '', FILTER_VALIDATE_INT);
        $now = now()->getTimestamp();
        abort_if($time === false || $time < $now - 3600 || $time > $now + 60, 401);
        $user = json_decode($fields['user'] ?? '', true);
        abort_unless(is_array($user) && is_int($user['id'] ?? null) && $user['id'] > 0, 401);

        return TelegramIdentityDTO::fromTelegram($user['id'], is_string($user['username'] ?? null) ? $user['username'] : null);
    }
}
