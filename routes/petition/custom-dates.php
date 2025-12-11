<?php

declare(strict_types=1);

use App\Enums\RouteName;
use App\Http\Controllers\Petition\PetitionCustomDatesController;
use Illuminate\Support\Facades\Route;

Route::prefix('/custom-dates')->group(static function (): void {
    Route::prefix('/edit')->group(static function (): void {
        Route::get('/', [PetitionCustomDatesController::class, 'edit'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_DATES_EDIT);
        Route::post('/', [PetitionCustomDatesController::class, 'update'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_DATES_UPDATE);
    });
    Route::get('/show', [PetitionCustomDatesController::class, 'show'])
        ->name(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_DATES_SHOW);
});
