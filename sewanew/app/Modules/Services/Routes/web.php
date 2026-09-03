<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Services\Http\Controllers\ServiceController;

/*
|--------------------------------------------------------------------------
| Services Module Routes
|--------------------------------------------------------------------------
*/

Route::prefix('services')->name('services.')->group(function () {
    Route::get('/', [ServiceController::class, 'index'])->name('index');
    Route::get('/{slug}', [ServiceController::class, 'show'])->name('show');
});
