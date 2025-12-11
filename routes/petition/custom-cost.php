<?php

declare(strict_types=1);

use App\Enums\RouteName;
use App\Http\Controllers\Petition\PetitionCustomCostsController;
use Illuminate\Support\Facades\Route;

Route::prefix('/custom-costs')->group(static function (): void {
    Route::prefix('/edit')->group(static function (): void {
        Route::get('/', [PetitionCustomCostsController::class, 'edit'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_COSTS_EDIT);
        Route::post('/', [PetitionCustomCostsController::class, 'update'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_COSTS_UPDATE);
    });
    Route::get('/show', [PetitionCustomCostsController::class, 'show'])
        ->name(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_COSTS_SHOW);
});
