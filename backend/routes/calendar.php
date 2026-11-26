<?php

declare(strict_types=1);

use App\Http\Controllers\Calendar\CalendarController;
use App\Http\Controllers\Calendar\MiniAppSessionController;
use App\Http\Middleware\MiniAppSession;
use Illuminate\Support\Facades\Route;

Route::get('/app', [CalendarController::class, 'index'])->name('mini-app');
Route::post('/app/session', MiniAppSessionController::class)->middleware('throttle:10,1,mini-app-login:');
Route::prefix('/app/calendars')->middleware(MiniAppSession::class)->group(function (): void {
    Route::post('/', [CalendarController::class, 'store'])->middleware('throttle:60,1,calendar-save:');
    Route::put('/{calendar}', [CalendarController::class, 'update'])->whereNumber('calendar')->middleware('throttle:60,1,calendar-save:');
    Route::post('/{calendar}/publish', [CalendarController::class, 'publish'])->whereNumber('calendar')->middleware('throttle:6,60,calendar-publish:');
});
