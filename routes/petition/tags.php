<?php

declare(strict_types=1);

use App\Enums\RouteName;
use App\Http\Controllers\Petition\PetitionTagController;
use Illuminate\Support\Facades\Route;

Route::prefix('/tags')->group(static function (): void {
    Route::get('/', [PetitionTagController::class, 'show'])
        ->name(RouteName::DEPARTMENTS_PETITIONS_TAGS_SHOW);
    Route::prefix('/edit')->group(static function (): void {
        Route::get('/', [PetitionTagController::class, 'edit'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_TAGS_EDIT);
        Route::post('/', [PetitionTagController::class, 'update'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_TAGS_UPDATE);
    });
});
