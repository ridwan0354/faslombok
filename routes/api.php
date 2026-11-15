<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [ApiController::class, 'login']);

Route::middleware(['web', 'judge'])->group(function () {
    Route::get('/me/assignments', [ApiController::class, 'assignments']);
    Route::post('/qr/resolve', [ApiController::class, 'resolveQr']);
    Route::get('/competitions/{competition}/rubric', [ApiController::class, 'getRubric']);
    Route::put('/entries/{entry}/scores/me', [ApiController::class, 'submitScore']);
    Route::post('/scores/sync', [ApiController::class, 'syncBatch']);
});
