<?php

declare(strict_types=1);

use App\Enums\RouteName;
use App\Http\Controllers\Petition\PetitionAssignedUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('/assign-secondary')->group(static function (): void {
    Route::get('/', [PetitionAssignedUserController::class, 'showAssignedSecondaryUser'])
        ->name(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_SECONDARY_SHOW);
    Route::prefix('/edit')->group(static function (): void {
        Route::get('/', [PetitionAssignedUserController::class, 'editAssignedSecondaryUser'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_SECONDARY_EDIT);
        Route::post('/', [PetitionAssignedUserController::class, 'updateAssignedSecondaryUser'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_SECONDARY_UPDATE);
    });
});
