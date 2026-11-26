<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Context;

/** Дата подарков может задаваться локальной мастерской для одного запроса. */
final class GiftClock
{
    public const string PREVIEW_TIME = 'advent.preview_time';

    public static function now(?string $timezone = null): Carbon
    {
        $preview = Context::getHidden(self::PREVIEW_TIME);
        if ($preview instanceof Carbon) {
            return $preview->copy()->setTimezone($timezone ?? 'UTC');
        }

        return now($timezone);
    }
}
