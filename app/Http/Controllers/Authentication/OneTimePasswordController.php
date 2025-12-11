<?php

declare(strict_types=1);

namespace App\Http\Controllers\Authentication;

use App\Actions\User\UserOtpEnableAction;
use App\Actions\User\UserOtpResetAction;
use App\Enums\RouteName;
use App\Events\OtpAuthenticationFailedEvent;
use App\Events\OtpDisabledEvent;
use App\Events\OtpEnabledEvent;
use App\Events\OtpEnrollmentConfirmedEvent;
use App\Http\Requests\Authentication\OneTimePassword\OneTimePasswordConfirmRequest;
use App\Http\Requests\Authentication\OneTimePassword\OneTimePasswordValidateRequest;
use App\Services\Authentication\AuthenticationException;
use App\Services\Authentication\AuthenticationRedirectResponseHelper;
use App\Services\Authentication\AuthenticationServiceInterface;
use App\Services\Authentication\OneTimePasswordService;
use App\Services\Authorisation\ActiveDepartmentService;
use App\Services\RateLimit\RateLimitExceededException;
use App\Services\RateLimit\RateLimitService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\UrlGenerator;

use function event;

final readonly class OneTimePasswordController
{
    public function __construct(
        private AuthenticationServiceInterface $authenticationService,
        private AuthenticationRedirectResponseHelper $redirectResponseHelper,
        private ActiveDepartmentService $activeDepartmentService,
        private OneTimePasswordService $oneTimePasswordService,
        private RateLimitService $rateLimitService,
        private Redirector $redirector,
        private UrlGenerator $urlGenerator,
        private Factory $view,
    ) {
    }

    /**
     * @throws AuthenticationException
     */
    public function authenticate(): View
    {
        return $this->view->make('authentication.one_time_password.form', [
            'user' => $this->authenticationService->user(),
        ]);
    }

    /**
     * @throws AuthenticationException
     */
    public function enroll(): View
    {
        return $this->view->make('authentication.one_time_password.enroll', [
            'user' => $this->authenticationService->user(),
        ]);
    }

    /**
     * @throws AuthenticationException
     * @throws RateLimitExceededException
     */
    public function authenticationAttempt(OneTimePasswordValidateRequest $request): RedirectResponse
    {
        $user = $this->authenticationService->user();
        $this->rateLimitService->check($request, $user->email);

        $verificationResult = $this->oneTimePasswordService->verifyCode($request->getString('code'), $user);
        if ($verificationResult === false) {
            return $this->redirector->back()
                ->withErrors(['code' => 'error']);
        }



        $activeDepartment = $this->activeDepartmentService->getActiveDepartment();
        $destination = $this->redirectResponseHelper->determineDestinationAfterAuthentication($activeDepartment);

        return $this->redirector->intended(
            $this->urlGenerator->route(
                $destination->route,
                $destination->parameters,
                false,
            ),
        );
    }

    /**
     * @throws AuthenticationException
     * @throws RateLimitExceededException
     */
    public function confirm(OneTimePasswordConfirmRequest $request): RedirectResponse
    {
        $user = $this->authenticationService->user();

        $this->rateLimitService->check($request, $user->email);

        $verificationResult = $this->oneTimePasswordService->verifyCode($request->getString('otp_confirmation'), $user);
        if ($verificationResult === false) {
            event(new OtpAuthenticationFailedEvent($user, ['email' => $user->email, 'action' => 'otp_enrollment_failed']));

            return $this->redirector->back()
                ->withErrors(['otp_confirmation' => 'error']);
        }

        event(new OtpEnrollmentConfirmedEvent($user, ['email' => $user->email, 'action' => 'otp_enrollment_confirmed']));

        return $this->redirector->route('profile.edit');
    }

    /**
     * @throws AuthenticationException
     */
    public function disable(
        UserOtpResetAction $action,
    ): RedirectResponse {
        $user = $this->authenticationService->user();

        $action->execute($user);

        event(new OtpDisabledEvent($user, ['email' => $user->email]));

        $this->authenticationService->logoutEverywhere();

        return $this->redirector->route(RouteName::LOGIN);
    }

    /**
     * @throws AuthenticationException
     */
    public function enable(
        UserOtpEnableAction $action,
    ): RedirectResponse {
        $user = $this->authenticationService->user();

        $action->execute($user);

        event(new OtpEnabledEvent($user, ['email' => $user->email, 'action' => 'otp_enabled']));

        return $this->redirector->route('one-time-password.enroll');
    }
}
