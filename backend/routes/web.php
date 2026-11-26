<?php

declare(strict_types=1);

use App\Http\Middleware\PreviewClock;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/app');
Route::middleware(PreviewClock::class)->group(function () {
    require __DIR__.'/calendar.php';
    if (app()->environment('local', 'testing')) {
        require __DIR__.'/dev.php';
    }
});
