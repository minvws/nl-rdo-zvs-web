<?php

declare(strict_types=1);

use App\Enums\RouteName;
use App\Http\Controllers\Petition\PetitionQuerysnapshotsController;
use Illuminate\Support\Facades\Route;

Route::prefix('/querysnapshots')->group(static function (): void {
    Route::prefix('/edit')->group(static function (): void {
        Route::get('/', [PetitionQuerysnapshotsController::class, 'edit'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_QUERYSNAPSHOTS_EDIT);
        Route::post('/', [PetitionQuerysnapshotsController::class, 'update'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_QUERYSNAPSHOTS_UPDATE);
    });
    Route::get('/show', [PetitionQuerysnapshotsController::class, 'show'])
        ->name(RouteName::DEPARTMENTS_PETITIONS_QUERYSNAPSHOTS_SHOW);
});
