<?php

declare(strict_types=1);

namespace App\Http\Controllers\Authentication;

use App\Actions\PasswordResetToken\PasswordResetTokenAction;
use App\Http\NotFoundException;
use App\Models\User;
use App\Services\Authentication\AuthenticationException;
use App\Services\Authentication\AuthenticationServiceInterface;
use App\Services\RateLimit\RateLimitExceededException;
use App\Services\RateLimit\RateLimitService;
use App\Services\User\UserException;
use App\Services\User\UserService;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

final readonly class VerifyEmailController
{
    public function __construct(
        private AuthenticationServiceInterface $authenticationService,
        private RateLimitService $rateLimitService,
        private Redirector $redirector,
        private UrlGenerator $urlGenerator,
        private UserService $userService,
        private Factory $view,
    ) {
    }

    /**
     * @throws AuthenticationException
     * @throws RateLimitExceededException
     */
    public function notify(Request $request): RedirectResponse
    {
        $user = $this->authenticationService->user();
        $this->rateLimitService->check($request, $user->email);

        if ($this->userService->hasVerifiedEmail($user)) {
            return $this->redirector->intended($this->urlGenerator->route('dashboard', absolute: false));
        }

        $this->userService->sendEmailVerificationMail($user);

        return $this->redirector->back()
            ->with('status', 'verification-link-sent');
    }

    /**
     * @throws AuthenticationException
     */
    public function verify(): RedirectResponse|View
    {
        $user = $this->authenticationService->user();

        if ($this->userService->hasVerifiedEmail($user)) {
            return $this->redirector->intended($this->urlGenerator->route('dashboard', absolute: false));
        }

        return $this->view->make('authentication.verify-email');
    }

    public function verifyAttempt(
        User $user,
        Request $request,
        PasswordResetTokenAction $action,
    ): RedirectResponse {
        if ($this->userService->hasVerifiedEmail($user)) {
            return $this->redirector->to('login');
        }

        try {
            $this->userService->verifyEmailByHash($user, $request->string('hash')->toString());
        } catch (UserException $userException) {
            throw NotFoundException::fromThrowable('user-hash not found', $userException);
        }

        $passwordResetToken = $action->execute($user->email);

        return $this->redirector->route('password.reset.request', [
            'id' => $passwordResetToken->id->toString(),
            'token' => $passwordResetToken->token,
        ]);
    }
}
