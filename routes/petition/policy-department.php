<?php

declare(strict_types=1);

use App\Enums\RouteName;
use App\Http\Controllers\Petition\PetitionPolicyDepartmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('/policy-department')->group(static function (): void {
    Route::get('/', [PetitionPolicyDepartmentController::class, 'show'])
        ->name(RouteName::DEPARTMENTS_PETITIONS_POLICY_DEPARTMENT_SHOW);
    Route::prefix('/edit')->group(static function (): void {
        Route::get('/', [PetitionPolicyDepartmentController::class, 'edit'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_POLICY_DEPARTMENT_EDIT);
        Route::post('/', [PetitionPolicyDepartmentController::class, 'update'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_POLICY_DEPARTMENT_UPDATE);
    });
});
