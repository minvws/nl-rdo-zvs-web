<?php

declare(strict_types=1);

use App\Enums\RouteName;
use App\Http\Controllers\Petition\PetitionArchiveController;
use Illuminate\Support\Facades\Route;

Route::prefix('/archive')->group(static function (): void {
    Route::post('/', [PetitionArchiveController::class, 'store'])
        ->name(RouteName::DEPARTMENTS_PETITIONS_ARCHIVE_STORE);
});
