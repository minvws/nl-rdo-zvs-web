<?php

declare(strict_types=1);

use App\Enums\RouteName;
use App\Http\Controllers\Petition\PetitionAttachController;
use Illuminate\Support\Facades\Route;

Route::prefix('/petition')->group(static function (): void {
    Route::get('/attach', [PetitionAttachController::class, 'attachForm'])
        ->name(RouteName::DEPARTMENTS_PETITION_PETITION_ATTACH_FORM);
    Route::post('/attach', [PetitionAttachController::class, 'attachPetitionToPetition'])
        ->name(RouteName::DEPARTMENTS_PETITION_PETITION_ATTACH);
    Route::post('/detach/{relatedPetition}', [PetitionAttachController::class, 'detachPetitionFromPetition'])
        ->name(RouteName::DEPARTMENTS_PETITION_PETITION_DETACH);
});
