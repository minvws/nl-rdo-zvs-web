<?php

declare(strict_types=1);

use App\Enums\RouteName;
use App\Http\Controllers\Petition\PetitionCustomPetitionPropertiesController;
use Illuminate\Support\Facades\Route;

Route::prefix('/custom-petition-property')->group(static function (): void {
    Route::get('/', [PetitionCustomPetitionPropertiesController::class, 'show'])
        ->name(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_SHOW);
    Route::prefix('/edit')->group(static function (): void {
        Route::get('/', [PetitionCustomPetitionPropertiesController::class, 'edit'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_EDIT);
        Route::post('/', [PetitionCustomPetitionPropertiesController::class, 'update'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_UPDATE);
    });
});
