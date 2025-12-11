<?php

declare(strict_types=1);

use App\Enums\RouteName;
use App\Http\Controllers\Petition\PetitionTermController;
use Illuminate\Support\Facades\Route;

Route::prefix('/term')->group(static function (): void {
    Route::prefix('/{termType}/create')->group(static function (): void {
        Route::get('/', [PetitionTermController::class, 'create'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_TERMS_CREATE);
        Route::post('/', [PetitionTermController::class, 'store'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_TERMS_STORE);
    });
    Route::prefix('/{petitionTerm}')->group(static function (): void {
        Route::get('/delete', [PetitionTermController::class, 'delete'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_TERMS_DELETE);
        Route::prefix('/edit')->group(static function (): void {
            Route::get('/', [PetitionTermController::class, 'edit'])
                ->name(RouteName::DEPARTMENTS_PETITIONS_TERMS_EDIT);
            Route::post('/', [PetitionTermController::class, 'update'])
                ->name(RouteName::DEPARTMENTS_PETITIONS_TERMS_UPDATE);
        });
    });
});
