<?php

declare(strict_types=1);

use App\Http\Controllers\Calendar\CalendarController;
use App\Http\Controllers\Calendar\MiniAppSessionController;
use App\Http\Controllers\Calendar\ReceivedCalendarController;
use App\Http\Middleware\MiniAppLaunch;
use App\Http\Middleware\MiniAppSession;
use Illuminate\Support\Facades\Route;

Route::get('/app', [CalendarController::class, 'index'])->middleware(MiniAppLaunch::class)->name('mini-app');
Route::get('/app/received/{calendar}/{date?}', [ReceivedCalendarController::class, 'show'])
    ->whereNumber('calendar')->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}')->middleware(MiniAppLaunch::class);
Route::post('/app/received/{calendar}/{date}/open', [ReceivedCalendarController::class, 'open'])
    ->whereNumber('calendar')->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}')->middleware([MiniAppLaunch::class, MiniAppSession::class, 'throttle:60,1,gift-open:']);
Route::post('/app/session', MiniAppSessionController::class)->middleware('throttle:10,1,mini-app-login:');
Route::prefix('/app/calendars')->middleware([MiniAppLaunch::class, MiniAppSession::class])->group(function (): void {
    Route::post('/', [CalendarController::class, 'store'])->middleware('throttle:60,1,calendar-save:');
    Route::put('/{calendar}', [CalendarController::class, 'update'])->whereNumber('calendar')->middleware('throttle:60,1,calendar-save:');
    Route::post('/{calendar}/publish', [CalendarController::class, 'publish'])->whereNumber('calendar')->middleware('throttle:6,60,calendar-publish:');
});
