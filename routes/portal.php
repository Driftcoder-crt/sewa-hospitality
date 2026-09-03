<?php

use App\Modules\Portal\Http\Controllers\DashboardController;
use App\Modules\Portal\Http\Controllers\DocumentsController;
use App\Modules\Portal\Http\Controllers\InvitationAcceptController;
use App\Modules\Portal\Http\Controllers\InvoicesController;
use App\Modules\Portal\Http\Controllers\MessagesController;
use App\Modules\Portal\Http\Controllers\MovesController;
use App\Modules\Portal\Http\Controllers\NotificationsController;
use App\Modules\Portal\Http\Controllers\ProfileController;
use App\Modules\Portal\Http\Controllers\QuoteAcceptController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Client portal — app.sewahospitality.com (area: app)
|--------------------------------------------------------------------------
| 04-modules/04-client-portal.md §3 surface. Every authenticated route
| roots its queries at the signed-in member's organization through
| Portal\Services\TenantAccess (the isolation matrix has one
| implementation, tested per-endpoint). All routes are noindex via
| SetRequestContext (area = app) + the layout meta.
|
| Realtime = Livewire islands + wire:poll (native transport always
| works; Ably is an optional upgrade — 11-realtime §3).
*/

Route::middleware(['auth', 'can:access-portal'])->group(function (): void {
    Route::get('/', DashboardController::class)->name('portal.dashboard');

    Route::get('/moves', [MovesController::class, 'index'])->name('portal.moves');
    Route::get('/moves/{move}', [MovesController::class, 'show'])->name('portal.moves.show');
    Route::get('/moves/{move}/documents', [DocumentsController::class, 'index'])->name('portal.documents');

    Route::get('/messages', [MessagesController::class, 'index'])->name('portal.messages');
    Route::get('/messages/{thread}', [MessagesController::class, 'show'])->name('portal.messages.show');
    Route::post('/messages/{thread}', [MessagesController::class, 'store'])
        ->middleware('throttle:public-writes')->name('portal.messages.store');

    Route::get('/notifications', [NotificationsController::class, 'index'])->name('portal.notifications');
    Route::post('/notifications/{notification}/read', [NotificationsController::class, 'read'])->name('portal.notifications.read');
    Route::post('/notifications/read-all', [NotificationsController::class, 'readAll'])->name('portal.notifications.read-all');

    Route::get('/invoices', [InvoicesController::class, 'index'])->name('portal.invoices');
    Route::get('/invoices/{invoice}', [InvoicesController::class, 'show'])->name('portal.invoices.show');
    Route::get('/invoices/{invoice}/download', [InvoicesController::class, 'download'])
        ->name('portal.invoices.download');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('portal.profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('portal.profile.update');
});

// Signed, expiring document download — the URL itself carries the
// authorization (15-minute validity; error-lock: originals never sit
// on public URLs).
Route::get('/documents/{document}/download', [DocumentsController::class, 'download'])
    ->middleware(['signed', 'auth', 'can:access-portal'])->name('portal.documents.download');

/*
| Quote acceptance (12-billing-finance §3): emailed secure token link —
| works for logged-in org members AND tokenized recipients; the token
| is single-use and expires with the quote.
*/
Route::get('/quotes/{quote}/accept/{token}', [QuoteAcceptController::class, 'show'])->name('portal.quotes.accept');
Route::post('/quotes/{quote}/accept/{token}', [QuoteAcceptController::class, 'decide'])
    ->middleware('throttle:public-writes')->name('portal.quotes.decide');

/*
| Invitation first-login (04 doc §4.5): magic set-password link from
| the portal.invite email — valid 72h, single-use.
*/
Route::get('/invitations/{token}', InvitationAcceptController::class)
    ->middleware('guest')->name('portal.invitations.accept');
Route::post('/invitations/{token}', [InvitationAcceptController::class, 'store'])
    ->middleware('guest', 'throttle:public-writes')->name('portal.invitations.store');
