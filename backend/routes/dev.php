<?php

declare(strict_types=1);

use App\Http\Controllers\DevController;
use App\Http\Middleware\LocalStudioOnly;
use Illuminate\Support\Facades\Route;

Route::controller(DevController::class)->middleware(LocalStudioOnly::class)->prefix('dev')->group(function () {
    Route::get('/', 'index');
    Route::post('staff', 'staff');
    Route::post('session', 'select');
    Route::post('start', 'start');
    Route::post('morning', 'morning');
    Route::get('widget/{message}', 'widget')->whereNumber('message');
    Route::post('widget/{message}/query', 'query')->whereNumber('message');
});
