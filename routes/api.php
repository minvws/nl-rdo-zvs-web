<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthenticationController;
use App\Http\Controllers\Api\GenericApiController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthenticationController::class, 'login']);

Route::prefix('/v1')->middleware('auth:sanctum')->group(static function (): void {
    Route::get('/{table}', [GenericApiController::class, 'index']);
});
