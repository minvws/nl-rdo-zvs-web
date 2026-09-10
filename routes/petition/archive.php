<?php

declare(strict_types=1);

use App\Enums\RouteName;
use App\Http\Controllers\Petition\PetitionArchiveController;
use App\Http\Controllers\Petition\PetitionUnarchiveController;
use Illuminate\Support\Facades\Route;

Route::prefix('/archive')->group(static function (): void {
    Route::post('/', [PetitionArchiveController::class, 'store'])
        ->name(RouteName::DEPARTMENTS_PETITIONS_ARCHIVE_STORE);
});

Route::post('/unarchive', PetitionUnarchiveController::class)
    ->name(RouteName::DEPARTMENTS_PETITIONS_UNARCHIVE_STORE);
