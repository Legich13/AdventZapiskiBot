<?php

declare(strict_types=1);

namespace App\Admin;

enum StaffPermission: string
{
    case Overview = 'View:Overview';
    case Handbook = 'View:Handbook';
}
