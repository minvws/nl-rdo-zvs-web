<?php

declare(strict_types=1);

use App\Enums\Ability;
use App\Enums\RouteName;
use App\Http\Controllers\DecisionAttachController;
use App\Http\Controllers\DecisionController;
use App\Models\Decision;
use Illuminate\Support\Facades\Route;

Route::prefix('/decisions')->group(static function (): void {
    Route::prefix('/attach')->group(static function (): void {
        Route::get('/', [DecisionAttachController::class, 'attachForm'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_DECISION_ATTACH_FORM);
        Route::post('/', [DecisionAttachController::class, 'attachDecisionToPetition'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_DECISION_ATTACH);
    });
    Route::prefix('/create')->group(static function (): void {
        Route::get('/', [DecisionController::class, 'create'])
            ->can(Ability::CREATE, Decision::class)
            ->name(RouteName::DEPARTMENTS_PETITIONS_DECISIONS_CREATE);
        Route::post('/', [DecisionController::class, 'store'])
            ->can(Ability::CREATE, Decision::class)
            ->name(RouteName::DEPARTMENTS_PETITIONS_DECISIONS_STORE);
    });
    Route::prefix('/{relatedDecision}')->group(static function (): void {
        Route::post('/detach', [DecisionAttachController::class, 'detachDecisionFromPetition'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_DECISION_DETACH);
    });
});
