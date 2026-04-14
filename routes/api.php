<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\TranslationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::post('/auth/token', [AuthController::class, 'token'])
        ->name('auth.token');

    Route::middleware('auth:sanctum')->group(function () {

        Route::apiResource('translations', TranslationController::class);

        Route::get('/export/{locale}', [ExportController::class, 'export'])
            ->name('export.locale');

        Route::apiResource('tags', TagController::class)
            ->only(['index', 'store']);
    });
});
