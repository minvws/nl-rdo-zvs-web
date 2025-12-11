<?php

declare(strict_types=1);

use App\Enums\RouteName;
use App\Http\Controllers\Petition\PetitionExternalUrlsController;
use Illuminate\Support\Facades\Route;

Route::prefix('/external-urls')->group(static function (): void {
    Route::prefix('/edit')->group(static function (): void {
        Route::get('/', [PetitionExternalUrlsController::class, 'edit'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_EXTERNAL_URLS_EDIT);
        Route::post('/', [PetitionExternalUrlsController::class, 'update'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_EXTERNAL_URLS_UPDATE);
    });
    Route::get('/show', [PetitionExternalUrlsController::class, 'show'])
        ->name(RouteName::DEPARTMENTS_PETITIONS_EXTERNAL_URLS_SHOW);
});
