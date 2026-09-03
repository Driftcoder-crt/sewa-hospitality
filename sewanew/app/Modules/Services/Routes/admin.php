<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Services\Http\Controllers\Admin\AdminServiceController;

/*
|--------------------------------------------------------------------------
| Services Module Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin/services')->name('admin.services.')->middleware(['auth', 'can:manage-services'])->group(function () {
    Route::get('/', [AdminServiceController::class, 'index'])->name('index');
    Route::get('/create', [AdminServiceController::class, 'create'])->name('create');
    Route::post('/', [AdminServiceController::class, 'store'])->name('store');
    Route::get('/{service}', [AdminServiceController::class, 'show'])->name('show');
    Route::get('/{service}/edit', [AdminServiceController::class, 'edit'])->name('edit');
    Route::put('/{service}', [AdminServiceController::class, 'update'])->name('update');
    Route::delete('/{service}', [AdminServiceController::class, 'destroy'])->name('destroy');
    Route::post('/{service}/toggle-publish', [AdminServiceController::class, 'togglePublish'])->name('toggle-publish');
});
