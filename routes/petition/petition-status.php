<?php

declare(strict_types=1);

use App\Enums\RouteName;
use App\Http\Controllers\Petition\PetitionStatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('/change-status/edit')->group(static function (): void {
    Route::get('/', [PetitionStatusController::class, 'edit'])
        ->name(RouteName::DEPARTMENTS_PETITIONS_CHANGE_STATUS_EDIT);
    Route::post('/', [PetitionStatusController::class, 'update'])
        ->name(RouteName::DEPARTMENTS_PETITIONS_CHANGE_STATUS_UPDATE);
});
