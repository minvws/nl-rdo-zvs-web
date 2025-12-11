<?php

declare(strict_types=1);

use App\Enums\RouteName;
use App\Http\Controllers\Petition\PetitionDraftTermController;
use Illuminate\Support\Facades\Route;

Route::prefix('/draft-term')->group(static function (): void {
    Route::prefix('/create')->group(static function (): void {
        Route::get('/', [PetitionDraftTermController::class, 'create'])
        ->name(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_CREATE);
        Route::post('/', [PetitionDraftTermController::class, 'store'])
        ->name(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_STORE);
    });
    Route::prefix('/edit')->group(static function (): void {
        Route::get('/', [PetitionDraftTermController::class, 'edit'])
        ->name(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_EDIT);
        Route::post('/', [PetitionDraftTermController::class, 'update'])
        ->name(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_UPDATE);
    });
    Route::get('/delete', [PetitionDraftTermController::class, 'delete'])
    ->name(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_DELETE);
});
