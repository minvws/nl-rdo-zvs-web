<?php

declare(strict_types=1);

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Http\Controllers\Petition\PetitionContactController;
use Illuminate\Support\Facades\Route;

Route::prefix('/contacts')
    ->middleware('can:' . Permission::CONTACT_MANAGE->value)
    ->group(static function (): void {
        Route::get('/attach-form', [PetitionContactController::class, 'showAttachForm'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH_FORM);
        Route::post('/attach-form', [PetitionContactController::class, 'showAttachForm'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH_FORM_FILTER);
    });
