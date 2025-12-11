<?php

declare(strict_types=1);

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Http\Controllers\Admin\PolicyDepartmentController;
use App\Http\Controllers\Admin\PublicHolidayController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AdminPanelController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdminPanelController::class, 'show'])
    ->middleware('can:' . Permission::ADMIN_PANEL_VIEW->value)
    ->name(RouteName::ADMIN_SHOW);

Route::prefix('/policy-departments')->group(static function (): void {
    Route::get('/', [PolicyDepartmentController::class, 'index'])
        ->middleware('can:' . Permission::POLICY_DEPARTMENT_WRITE->value)
        ->name(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX);

    Route::prefix('/create')->group(static function (): void {
        Route::get('/', [PolicyDepartmentController::class, 'create'])
            ->middleware('can:' . Permission::POLICY_DEPARTMENT_WRITE->value)
            ->name(RouteName::ADMIN_POLICY_DEPARTMENT_CREATE);
        Route::post('/', [PolicyDepartmentController::class, 'store'])
            ->middleware('can:' . Permission::POLICY_DEPARTMENT_WRITE->value)
            ->name(RouteName::ADMIN_POLICY_DEPARTMENT_STORE);
    });

    Route::prefix('/{policyDepartment}')->group(static function (): void {
        Route::prefix('/edit')->group(static function (): void {
            Route::get('/', [PolicyDepartmentController::class, 'edit'])
                ->middleware('can:' . Permission::POLICY_DEPARTMENT_WRITE->value)
                ->name(RouteName::ADMIN_POLICY_DEPARTMENT_EDIT);
            Route::post('/', [PolicyDepartmentController::class, 'update'])
                ->middleware('can:' . Permission::POLICY_DEPARTMENT_WRITE->value)
                ->name(RouteName::ADMIN_POLICY_DEPARTMENT_UPDATE);
        });
    });
});

Route::prefix('/public-holidays')->group(static function (): void {
    Route::get('/', [PublicHolidayController::class, 'index'])
        ->middleware('can:' . Permission::PUBLIC_HOLIDAY_READ->value)
        ->name(RouteName::ADMIN_PUBLIC_HOLIDAY_INDEX);

    Route::prefix('/create')->group(static function (): void {
        Route::get('/create', [PublicHolidayController::class, 'create'])
            ->middleware('can:' . Permission::PUBLIC_HOLIDAY_WRITE->value)
            ->name(RouteName::ADMIN_PUBLIC_HOLIDAY_CREATE);
        Route::post('/create', [PublicHolidayController::class, 'store'])
            ->middleware('can:' . Permission::PUBLIC_HOLIDAY_WRITE->value)
            ->name(RouteName::ADMIN_PUBLIC_HOLIDAY_STORE);
    });

    Route::prefix('/{publicHoliday}')->group(static function (): void {
        Route::prefix('/edit')->group(static function (): void {
            Route::get('/', [PublicHolidayController::class, 'edit'])
                ->middleware('can:' . Permission::PUBLIC_HOLIDAY_WRITE->value)
                ->name(RouteName::ADMIN_PUBLIC_HOLIDAY_EDIT);
            Route::post('/', [PublicHolidayController::class, 'update'])
                ->middleware('can:' . Permission::PUBLIC_HOLIDAY_WRITE->value)
                ->name(RouteName::ADMIN_PUBLIC_HOLIDAY_UPDATE);
        });
        Route::delete('/', [PublicHolidayController::class, 'delete'])
            ->middleware('can:' . Permission::PUBLIC_HOLIDAY_WRITE->value)
            ->name(RouteName::ADMIN_PUBLIC_HOLIDAY_DELETE);
    });
});

Route::prefix('/users')->group(static function (): void {
    Route::get('/', [UserController::class, 'index'])
        ->middleware('can:' . Permission::USER_READ->value)
        ->name(RouteName::ADMIN_USER_INDEX);

    Route::prefix('/create')->group(static function (): void {
        Route::get('/', [UserController::class, 'create'])
            ->middleware('can:' . Permission::USER_WRITE->value)
            ->name(RouteName::ADMIN_USER_CREATE);
        Route::post('/', [UserController::class, 'store'])
            ->middleware('can:' . Permission::USER_WRITE->value)
            ->name(RouteName::ADMIN_USER_STORE);
    });

    Route::prefix('/{user}')->group(static function (): void {
        Route::prefix('/edit')->group(static function (): void {
            Route::get('/', [UserController::class, 'edit'])
                ->middleware('can:' . Permission::USER_WRITE->value)
                ->name(RouteName::ADMIN_USER_EDIT);
            Route::post('/', [UserController::class, 'update'])
                ->middleware('can:' . Permission::USER_WRITE->value)
                ->name(RouteName::ADMIN_USER_UPDATE);
        });
        Route::post('/otp-reset', [UserController::class, 'otpReset'])
            ->middleware('can:' . Permission::USER_WRITE->value)
            ->name(RouteName::ADMIN_USER_OTP_RESET);
    });
});
