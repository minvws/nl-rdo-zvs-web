<?php

declare(strict_types=1);

use App\Enums\RouteName;
use App\Http\Controllers\PetitionEventWizardController;
use Illuminate\Support\Facades\Route;

Route::get('/petition-events/reset', [PetitionEventWizardController::class, 'reset'])
    ->name(RouteName::PETITION_EVENTS_WIZARD_RESET);
Route::get('/petition-events/start/', [PetitionEventWizardController::class, 'start'])
    ->name(RouteName::PETITION_EVENTS_WIZARD_STEP);
Route::get('/petition-events/create/{type}', [PetitionEventWizardController::class, 'create'])
    ->name(RouteName::PETITION_EVENTS_WIZARD_CREATE);
Route::post('/petition-events/add', [PetitionEventWizardController::class, 'add'])
    ->name(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM);
Route::post('/petition-events/delete-last', [PetitionEventWizardController::class, 'deleteLast'])
    ->name(RouteName::PETITION_EVENTS_WIZARD_DELETE_LAST);
Route::post('/petition-events/store', [PetitionEventWizardController::class, 'store'])
    ->name(RouteName::PETITION_EVENTS_WIZARD_STORE);
