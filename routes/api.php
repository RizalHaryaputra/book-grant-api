<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PublisherController;

Route::prefix('publisher')->group(function () {

    Route::get('/preprint-list', [PublisherController::class, 'preprintList']);

    Route::post('/check', [PublisherController::class, 'checkStore']);

    Route::post('/decision', [PublisherController::class, 'decisionStore']);
});