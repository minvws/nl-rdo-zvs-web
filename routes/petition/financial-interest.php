<?php

declare(strict_types=1);

use App\Enums\RouteName;
use App\Http\Controllers\Petition\PetitionFinancialInterestController;
use Illuminate\Support\Facades\Route;

Route::prefix('/financial-interest')->group(static function (): void {
    Route::get('/', [PetitionFinancialInterestController::class, 'show'])
        ->name(RouteName::DEPARTMENTS_PETITIONS_FINANCIAL_INTEREST_SHOW);
    Route::prefix('/edit')->group(static function (): void {
        Route::get('/', [PetitionFinancialInterestController::class, 'edit'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_FINANCIAL_INTEREST_EDIT);
        Route::post('/', [PetitionFinancialInterestController::class, 'update'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_FINANCIAL_INTEREST_UPDATE);
    });
});
