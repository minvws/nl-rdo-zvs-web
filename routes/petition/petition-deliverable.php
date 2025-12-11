<?php

declare(strict_types=1);

use App\Enums\RouteName;
use App\Http\Controllers\Petition\PetitionDeliverableController;
use Illuminate\Support\Facades\Route;

Route::prefix('/petition_deliverable')->group(static function (): void {
    Route::prefix('/{petitionDeliverableType}/create')->group(static function (): void {
        Route::get('/', [PetitionDeliverableController::class, 'create'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_PETITION_DELIVERABLE_CREATE);
        Route::post('/', [PetitionDeliverableController::class, 'store'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_PETITION_DELIVERABLE_STORE);
    });
    Route::prefix('/{petitionDeliverable}')->group(static function (): void {
        Route::get('/delete', [PetitionDeliverableController::class, 'delete'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_PETITION_DELIVERABLE_DELETE);
        Route::prefix('/edit')->group(static function (): void {
            Route::get('/', [PetitionDeliverableController::class, 'edit'])
                ->name(RouteName::DEPARTMENTS_PETITIONS_PETITION_DELIVERABLE_EDIT);
            Route::post('/', [PetitionDeliverableController::class, 'update'])
                ->name(RouteName::DEPARTMENTS_PETITIONS_PETITION_DELIVERABLE_UPDATE);
        });
    });
});
