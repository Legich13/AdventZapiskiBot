<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('calendar:morning')->everyMinute()->timezone('Europe/Moscow')->withoutOverlapping(5);
