<?php

declare(strict_types=1);

use App\Enums\RouteName;
use App\Http\Controllers\Petition\PetitionCorrespondenceController;
use Illuminate\Support\Facades\Route;

Route::prefix('/correspondence')->group(static function (): void {
    Route::get('/', [PetitionCorrespondenceController::class, 'show'])
        ->name(RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_SHOW);
    Route::prefix('/edit')->group(static function (): void {
        Route::get('/', [PetitionCorrespondenceController::class, 'edit'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_EDIT);
        Route::post('/', [PetitionCorrespondenceController::class, 'update'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_UPDATE);
    });
    Route::prefix('/word-templates')->group(static function (): void {
        Route::get('/', [PetitionCorrespondenceController::class, 'index'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_INDEX);
        Route::get('/{word_template_id}', [PetitionCorrespondenceController::class, 'download'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_WORD_TEMPLATE_DOWNLOAD);
    });
});
