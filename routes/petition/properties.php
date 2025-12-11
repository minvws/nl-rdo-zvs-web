<?php

declare(strict_types=1);

use App\Enums\RouteName;
use App\Http\Controllers\Petition\PetitionPropertiesController;
use Illuminate\Support\Facades\Route;

Route::prefix('/properties')->group(static function (): void {
    Route::get('/', [PetitionPropertiesController::class, 'show'])
        ->name(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_SHOW);

    Route::prefix('/edit')->group(static function (): void {
        Route::get('/', [PetitionPropertiesController::class, 'edit'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_EDIT);
        Route::post('/', [PetitionPropertiesController::class, 'update'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_UPDATE);
    });
});
