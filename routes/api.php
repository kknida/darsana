<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SapBotController;

Route::middleware(['sap.token', 'throttle:60,1'])->group(function () {
    Route::get('/sap/settings', [SapBotController::class, 'settings']);
    Route::get('/sap/credentials', [SapBotController::class, 'credentials']);
    Route::post('/sap/import', [SapBotController::class, 'import']);
    Route::post('/sap/heartbeat', [SapBotController::class, 'heartbeat']);
});
