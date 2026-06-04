<?php

declare(strict_types=1);

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Http\Controllers\Contact\ContactArchiveController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Decision\DecisionArchiveController;
use App\Http\Controllers\Decision\DecisionUnarchiveController;
use App\Http\Controllers\DecisionController;
use App\Http\Controllers\DecisionPetitionAttachController;
use App\Http\Controllers\Petition\PetitionCategoryController;
use App\Http\Controllers\Petition\PetitionContactController;
use App\Http\Controllers\Petition\PetitionCreateController;
use App\Http\Controllers\Petition\PetitionExportController;
use App\Http\Controllers\Petition\PetitionFilterController;
use App\Http\Controllers\Petition\PetitionIndexController;
use App\Http\Controllers\Petition\PetitionShowController;
use App\Http\Controllers\Petition\PetitionStoreController;
use App\Http\Controllers\Petition\PetitionTypeController;
use App\Http\Controllers\Petition\TeamController;
use App\Http\Controllers\ProcessingStepController;
use App\Http\Controllers\TimelineableNoteController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::prefix('/admin')->group(static function (): void {
    Route::prefix('/petition-types')->group(static function (): void {
        Route::get('/', [PetitionTypeController::class, 'index'])
            ->middleware('can:' . Permission::PETITION_TYPE_READ->value)
            ->name(RouteName::DEPARTMENTS_ADMIN_PETITION_TYPES_INDEX);
        Route::prefix('/create')->group(static function (): void {
            Route::get('/', [PetitionTypeController::class, 'create'])
                ->middleware('can:' . Permission::PETITION_TYPE_WRITE->value)
                ->name(RouteName::DEPARTMENTS_ADMIN_PETITION_TYPES_CREATE);
            Route::post('/', [PetitionTypeController::class, 'store'])
                ->middleware('can:' . Permission::PETITION_TYPE_WRITE->value);
        });
        Route::prefix('/{petitionType}/edit')->group(static function (): void {
            Route::get('/', [PetitionTypeController::class, 'edit'])
                ->middleware('can:' . Permission::PETITION_TYPE_WRITE->value)
                ->name(RouteName::DEPARTMENTS_ADMIN_PETITION_TYPES_EDIT);
            Route::post('/', [PetitionTypeController::class, 'update'])
                ->middleware('can:' . Permission::PETITION_TYPE_WRITE->value);
        });
    });

    Route::prefix('/petition-categories')->group(static function (): void {
        Route::get('/', [PetitionCategoryController::class, 'index'])
            ->middleware('can:' . Permission::PETITION_CATEGORY_READ->value)
            ->name(RouteName::DEPARTMENTS_ADMIN_PETITION_CATEGORIES_INDEX);
        Route::prefix('/create')->group(static function (): void {
            Route::get('/', [PetitionCategoryController::class, 'create'])
                ->middleware('can:' . Permission::PETITION_CATEGORY_WRITE->value)
                ->name(RouteName::DEPARTMENTS_ADMIN_PETITION_CATEGORIES_CREATE);
            Route::post('/', [PetitionCategoryController::class, 'store'])
                ->middleware('can:' . Permission::PETITION_CATEGORY_WRITE->value);
        });
        Route::prefix('/{petitionCategory}/edit')->group(static function (): void {
            Route::get('/', [PetitionCategoryController::class, 'edit'])
                ->middleware('can:' . Permission::PETITION_CATEGORY_WRITE->value)
                ->name(RouteName::DEPARTMENTS_ADMIN_PETITION_CATEGORIES_EDIT);
            Route::post('/', [PetitionCategoryController::class, 'update'])
                ->middleware('can:' . Permission::PETITION_CATEGORY_WRITE->value);
        });
    });

    Route::prefix('/teams')->group(static function (): void {
        Route::get('/', [TeamController::class, 'index'])
            ->middleware('can:' . Permission::TEAM_READ->value)
            ->name(RouteName::DEPARTMENTS_ADMIN_TEAMS_INDEX);
        Route::prefix('/create')->group(static function (): void {
            Route::get('/', [TeamController::class, 'create'])
                ->middleware('can:' . Permission::TEAM_WRITE->value)
                ->name(RouteName::DEPARTMENTS_ADMIN_TEAMS_CREATE);
            Route::post('/', [TeamController::class, 'store'])
                ->middleware('can:' . Permission::TEAM_WRITE->value);
        });
        Route::prefix('/{team}/edit')->group(static function (): void {
            Route::get('/', [TeamController::class, 'edit'])
                ->middleware('can:' . Permission::TEAM_WRITE->value)
                ->name(RouteName::DEPARTMENTS_ADMIN_TEAMS_EDIT);
            Route::post('/', [TeamController::class, 'update'])
                ->middleware('can:' . Permission::TEAM_WRITE->value);
        });
    });
});

Route::prefix('/contacts')->group(static function (): void {
    Route::get('/', [ContactController::class, 'index'])
        ->middleware('can:' . Permission::CONTACT_READ->value)
        ->name(RouteName::DEPARTMENTS_CONTACTS_INDEX);
    Route::post('/filter', [ContactController::class, 'filter'])
        ->middleware('can:' . Permission::CONTACT_READ->value)
        ->name(RouteName::DEPARTMENTS_CONTACTS_INDEX_FILTER);
    Route::prefix('/create')->group(static function (): void {
        Route::get('/', [ContactController::class, 'create'])
            ->middleware('can:' . Permission::CONTACT_WRITE->value)
            ->name(RouteName::DEPARTMENTS_CONTACTS_CREATE);
        Route::post('/', [ContactController::class, 'store'])
            ->middleware('can:' . Permission::CONTACT_WRITE->value);
    });
    Route::prefix('/{contact}')->group(static function (): void {
        Route::scopeBindings()->group(static function (): void {
            Route::get('/show', [ContactController::class, 'show'])
                ->middleware('can:' . Permission::CONTACT_READ->value)
                ->name(RouteName::DEPARTMENTS_CONTACTS_SHOW);
            Route::prefix('/edit')->group(static function (): void {
                Route::get('/', [ContactController::class, 'edit'])
                    ->middleware('can:' . Permission::CONTACT_WRITE->value)
                    ->name(RouteName::DEPARTMENTS_CONTACTS_EDIT);
                Route::post('/', [ContactController::class, 'update'])
                    ->middleware('can:' . Permission::CONTACT_WRITE->value);
            });
            Route::prefix('/archive')->group(static function (): void {
                Route::post('/', [ContactArchiveController::class, 'store'])
                    ->middleware('can:' . Permission::CONTACT_WRITE->value)
                    ->name(RouteName::DEPARTMENTS_CONTACTS_ARCHIVE_STORE);
            });
        });
    });
});

Route::prefix('/exports')->group(static function (): void {
    Route::scopeBindings()->group(static function (): void {
        Route::get('/', [PetitionExportController::class, 'index'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_EXPORTS_INDEX);
        Route::post('/export', [PetitionExportController::class, 'export'])
            ->name(RouteName::DEPARTMENTS_PETITIONS_EXPORTS_EXPORT);
        Route::prefix('/{petitionExport}')->group(static function (): void {
            Route::get('/download', [PetitionExportController::class, 'download'])
                ->name(RouteName::DEPARTMENTS_PETITIONS_EXPORTS_DOWNLOAD);
            Route::get('/delete', [PetitionExportController::class, 'delete'])
                ->name(RouteName::DEPARTMENTS_PETITIONS_EXPORTS_DELETE);
        });
    });
});

Route::get('/decisions', [DecisionController::class, 'index'])
    ->name(RouteName::DEPARTMENTS_DECISIONS_INDEX);

Route::post('/decisions/filter', [DecisionController::class, 'filter'])
    ->name(RouteName::DEPARTMENTS_DECISIONS_INDEX_FILTER);

Route::prefix('/decisions/create')->group(static function (): void {
    Route::get('/', [DecisionController::class, 'create'])
        ->name(RouteName::DEPARTMENTS_DECISIONS_CREATE);
    Route::post('/', [DecisionController::class, 'store'])
        ->name(RouteName::DEPARTMENTS_DECISIONS_STORE);
});

Route::prefix('/decisions/{decision}')->group(static function (): void {
    Route::scopeBindings()->group(static function (): void {
        Route::get('/', [DecisionController::class, 'show'])
            ->name(RouteName::DEPARTMENTS_DECISIONS_SHOW);
        Route::prefix('/edit')->group(static function (): void {
            Route::get('/', [DecisionController::class, 'edit'])
                ->name(RouteName::DEPARTMENTS_DECISIONS_EDIT);
            Route::post('/', [DecisionController::class, 'update'])
                ->name(RouteName::DEPARTMENTS_DECISIONS_UPDATE);
        });

        Route::prefix('/petition')->group(static function (): void {
            Route::get('/attach', [DecisionPetitionAttachController::class, 'attachForm'])
                ->name(RouteName::DEPARTMENTS_DECISION_PETITION_ATTACH_FORM);
            Route::post('/attach', [DecisionPetitionAttachController::class, 'attachPetitionToDecision'])
                ->name(RouteName::DEPARTMENTS_DECISION_PETITION_ATTACH);
            Route::get('/detach/{relatedPetition}', [DecisionPetitionAttachController::class, 'detachPetitionFromDecision'])
                ->name(RouteName::DEPARTMENTS_DECISION_PETITION_DETACH);
        });

        Route::get('/properties', [DecisionController::class, 'showProperties'])
            ->name(RouteName::DEPARTMENTS_DECISIONS_PROPERTIES);

        Route::prefix('/processing-steps')->group(static function (): void {
            Route::prefix('/create')->group(static function (): void {
                Route::get('/', [ProcessingStepController::class, 'create'])
                    ->name(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_CREATE);
                Route::post('/', [ProcessingStepController::class, 'store'])
                    ->name(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_STORE);
            });

            Route::prefix('/{processingStep}')->group(static function (): void {
                Route::get('/delete', [ProcessingStepController::class, 'delete'])
                    ->name(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_DELETE);

                Route::post('/move-up', [ProcessingStepController::class, 'move'])
                    ->defaults('direction', 'up')
                    ->name(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_MOVE_UP);

                Route::post('/move-down', [ProcessingStepController::class, 'move'])
                    ->defaults('direction', 'down')
                    ->name(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_MOVE_DOWN);

                Route::prefix('/edit')->group(static function (): void {
                    Route::get('/', [ProcessingStepController::class, 'edit'])
                        ->name(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_EDIT);
                    Route::post('/', [ProcessingStepController::class, 'update'])
                        ->name(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_UPDATE);
                });
            });
        });

        Route::post('/archive', DecisionArchiveController::class)
            ->name(RouteName::DEPARTMENTS_DECISIONS_ARCHIVE_STORE);

        Route::post('/unarchive', DecisionUnarchiveController::class)
            ->name(RouteName::DEPARTMENTS_DECISIONS_UNARCHIVE_STORE);
    });
});

Route::prefix('/petitions')->group(static function (): void {
    Route::get('/', PetitionIndexController::class)
        ->name(RouteName::DEPARTMENTS_PETITIONS_INDEX);
    Route::post('/filter', PetitionFilterController::class)
        ->name(RouteName::DEPARTMENTS_PETITIONS_INDEX_FILTER);

    Route::prefix('/create/{petitionType}')->group(static function (): void {
        Route::get('/', PetitionCreateController::class)
            ->name(RouteName::DEPARTMENTS_PETITIONS_CREATE);
        Route::post('/', PetitionStoreController::class)
            ->name(RouteName::DEPARTMENTS_PETITIONS_STORE);
    });

    Route::prefix('/{petition}')->group(static function (): void {
        Route::scopeBindings()->group(static function (): void {
            Route::get('/', PetitionShowController::class)
            ->name(RouteName::DEPARTMENTS_PETITIONS_SHOW);
            foreach (File::glob(base_path('routes/petition/*.php')) as $file) {
                require $file;
            }
        });
    });

    Route::prefix('/{timelineableType}/{timelineable}/{url}/note')->group(static function (): void {
        Route::prefix('/create')->group(static function (): void {
            Route::get('/', [TimelineableNoteController::class, 'create'])
                ->name(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_CREATE);
            Route::post('/', [TimelineableNoteController::class, 'store'])
                ->name(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_STORE);
        });
    });
});

// Petition contact actions - moved outside scoped bindings to avoid child binding issues
// Security is handled by manual checks in the controller
Route::prefix('/petitions/{petition}/contact-actions')->scopeBindings()->group(static function (): void {
    Route::post('/attach/{contact}', [PetitionContactController::class, 'attachContact'])
        ->name(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH)->withoutScopedBindings();
    Route::post('/detach/{contact}', [PetitionContactController::class, 'detachContact'])
        ->name(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_DETACH)
        ->scopeBindings();
    Route::post('/update-pivot/{contact}', [PetitionContactController::class, 'updateContactPivot'])
        ->name(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_UPDATE_PIVOT)
        ->scopeBindings();
});
