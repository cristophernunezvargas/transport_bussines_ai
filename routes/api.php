<?php

use App\Http\Controllers\Api\DriverWorklogImportController;
use App\Http\Middleware\EnsureIntegrationToken;
use Illuminate\Support\Facades\Route;

// Machine-to-machine routes for n8n: bearer token (config/integrations.php), no session.
Route::middleware([EnsureIntegrationToken::class, 'throttle:60,1'])->prefix('integrations')->group(function () {
    Route::post('driver-worklogs', DriverWorklogImportController::class)->name('api.driver-worklogs.import');
});
