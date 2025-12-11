<?php

declare(strict_types=1);

use App\Enums\RouteName;
use App\Http\Controllers\Petition\PetitionAssignedUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('/assign-user')->group(static function (): void {
    Route::get('/', [PetitionAssignedUserController::class, 'showAssignedUser'])
        ->name(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_USER_SHOW);
    Route::prefix('/edit')->group(static function (): void {
        Route::get('/', [PetitionAssignedUserController::class, 'editAssignedUser'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_USER_EDIT);
        Route::post('/', [PetitionAssignedUserController::class, 'updateAssignedUser'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_USER_UPDATE);
    });
});
