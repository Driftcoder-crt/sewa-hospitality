<?php

use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Versioned REST API — api.sewahospitality.com/v1 (area: api)
|--------------------------------------------------------------------------
| Mounted with the `api` middleware group and `/v1` prefix in
| bootstrap/app.php. Sanctum tokens + scopes arrive with the portal
| milestone; M0 ships the health probe the whole runbook depends on.
|
| Full endpoint map: 03-technical-specs/04-api-spec.md
*/

Route::get('/health', HealthController::class)
    ->name('api.health');
