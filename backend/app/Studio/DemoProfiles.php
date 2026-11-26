<?php

declare(strict_types=1);

namespace App\Studio;

final class DemoProfiles
{
    public const array PEOPLE = [90001 => 'winter_author', 90002 => 'winter_guest', 90003 => 'winter_friend'];

    public static function contains(int $id): bool
    {
        return isset(self::PEOPLE[$id]);
    }
}
