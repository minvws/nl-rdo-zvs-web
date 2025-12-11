<?php

declare(strict_types=1);

use App\Enums\RouteName;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Authentication\AuthenticationController;
use App\Http\Controllers\Authentication\OneTimePasswordController;
use App\Http\Controllers\Authentication\PasswordController;
use App\Http\Controllers\Authentication\VerifyEmailController;
use App\Http\Controllers\ConfirmController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentPanelController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\NotificationIndexController;
use App\Http\Controllers\NotificationMarkAllAsReadController;
use App\Http\Controllers\NotificationMarkAsReadController;
use App\Http\Controllers\NotificationMarkAsUnreadController;
use App\Http\Controllers\NotificationShowController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\RememberActiveDepartmentMiddleware;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

Route::get('/health', HealthController::class);

Route::prefix('/forgot-password')->group(static function (): void {
    Route::get('/', [PasswordController::class, 'forgotPassword'])->name(RouteName::FORGOT_PASSWORD_REQUEST);
    Route::post('/', [PasswordController::class, 'forgotPasswordMail'])->name(RouteName::FORGOT_PASSWORD_EMAIL);
});
Route::prefix('/login')->group(static function (): void {
    Route::get('/', [AuthenticationController::class, 'login'])->name(RouteName::LOGIN);
    Route::post('/', [AuthenticationController::class, 'loginAttempt'])->name(RouteName::LOGIN_ATTEMPT);
});
Route::prefix('/reset-password')->group(static function (): void {
    Route::get('/', [PasswordController::class, 'reset'])->name('password.reset.request');
    Route::post('/', [PasswordController::class, 'resetAttempt'])->name('password.reset.store');
});
Route::get('/email/verify/{user}', [VerifyEmailController::class, 'verifyAttempt'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::middleware(['auth', 'auth.session'])->group(static function (): void {
    Route::prefix('/email')->group(static function (): void {
        Route::get('/verify', [VerifyEmailController::class, 'verify'])->name('verification.notice');
        Route::post('/verification-notification', [VerifyEmailController::class, 'notify'])
            ->name('verification.send')
            ->middleware('throttle:6,1');
    });
    Route::post('/logout', [AuthenticationController::class, 'logout'])->name('logout');
    Route::put('/password', [PasswordController::class, 'update'])->name(RouteName::PASSWORD_UPDATE);

    Route::middleware('email-address-verified')->group(static function (): void {
        Route::prefix('/one-time-password')->group(static function (): void {
            Route::get('/', [OneTimePasswordController::class, 'authenticate'])->name('one-time-password.authenticate');
            Route::post('/', [OneTimePasswordController::class, 'authenticationAttempt']);
        });

        Route::middleware('one-time-password-not-enabled')->group(static function (): void {
            Route::prefix('/one-time-password')->group(static function (): void {
                Route::get('/enroll', [OneTimePasswordController::class, 'enroll'])->name('one-time-password.enroll');
                Route::post('/confirm', [OneTimePasswordController::class, 'confirm'])->name('one-time-password.confirm');
                Route::post('/enable', [OneTimePasswordController::class, 'enable'])->name('one-time-password.enable');
            });

            Route::middleware('one-time-password-authenticated')->group(static function (): void {
                Route::prefix('/profile')->group(static function (): void {
                    Route::get('/', [ProfileController::class, 'edit'])->name('profile.edit');
                    Route::post('/', [ProfileController::class, 'update']);
                });

                Route::get('/', DashboardController::class)->name('dashboard');

                Route::post('/disable', [OneTimePasswordController::class, 'disable'])->name('profile.otp.disable');

                Route::prefix('/admin')->group(base_path('routes/admin.php'));

                Route::prefix('/attachments')->name('attachments.')->group(static function (): void {
                    Route::get('/{attachment}/download', [AttachmentController::class, 'download'])
                        ->name('download');
                });

                Route::get('/departments', [DepartmentPanelController::class, 'show'])
                    ->name(RouteName::DEPARTMENTS_SHOW);

                Route::prefix('/{department}')
                    ->middleware(RememberActiveDepartmentMiddleware::class)
                    ->group(base_path('routes/department.php'));

                Route::get('/confirm', ConfirmController::class)
                    ->name(RouteName::CONFIRM);

                Route::get('/notifications', NotificationIndexController::class)
                    ->name(RouteName::NOTIFICATIONS_INDEX);
                Route::get('/notifications/{notification}', NotificationShowController::class)
                    ->name(RouteName::NOTIFICATIONS_SHOW);
                Route::post('/notifications/mark-all-read', NotificationMarkAllAsReadController::class)
                    ->name(RouteName::NOTIFICATIONS_MARK_ALL_READ);
                Route::post('/notifications/{notification}/mark-as-read', NotificationMarkAsReadController::class)
                    ->name(RouteName::NOTIFICATIONS_MARK_AS_READ);
                Route::post('/notifications/{notification}/mark-as-unread', NotificationMarkAsUnreadController::class)
                    ->name(RouteName::NOTIFICATIONS_MARK_AS_UNREAD);
            });
        });
    });
});

Route::any('{any}', static function (): void {
    throw new NotFoundHttpException();
})->where('any', '.*');
