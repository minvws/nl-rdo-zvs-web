<?php

declare(strict_types=1);

namespace App\Http\Controllers\Authentication;

use App\Actions\Authentication\LoginAttemptAction;
use App\Enums\RouteName;
use App\Http\Requests\Authentication\LoginStoreRequest;
use App\Models\Department;
use App\Services\Authentication\AuthenticationException;
use App\Services\Authentication\AuthenticationRedirectResponseHelper;
use App\Services\Authentication\AuthenticationServiceInterface;
use App\Services\Authorisation\ActiveDepartmentService;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Validation\ValidationException;

use function __;
use function abort;

final readonly class AuthenticationController
{
    public function __construct(
        private AuthenticationServiceInterface $authenticationService,
        private AuthenticationRedirectResponseHelper $redirectResponseHelper,
        private ActiveDepartmentService $activeDepartmentService,
        private Redirector $redirector,
        private UrlGenerator $urlGenerator,
        private Factory $view,
        private AuthManager $authManager,
    ) {
    }

    public function login(): View|RedirectResponse
    {
        if ($this->authManager->check()) {
            return $this->redirector->route('dashboard');
        }

        return $this->view->make('authentication.login');
    }

    /**
     * @throws ValidationException|AuthenticationException
     */
    public function loginAttempt(LoginStoreRequest $request, LoginAttemptAction $action): RedirectResponse
    {
        $email = $request->getString('email');
        $password = $request->getString('password');
        $remember = $request->getBoolean('remember');

        $success = $action->execute($request, $email, $password, $remember);

        if (!$success) {
            return $this->redirector->route(RouteName::LOGIN)
                ->withErrors(['authentication' => __('authentication.login_failed')]);
        }

        $activeDepartment = $this->determineActiveDepartment();
        $destination = $this->redirectResponseHelper->determineDestinationAfterAuthentication($activeDepartment);

        return $this->redirector->intended(
            $this->urlGenerator->route(
                $destination->route,
                $destination->parameters,
                false,
            ),
        );
    }

    public function logout(): RedirectResponse
    {
        $this->authenticationService->logout();

        return $this->redirector->route('login');
    }

    private function determineActiveDepartment(): ?Department
    {
        try {
            $user = $this->authenticationService->user();
        } catch (AuthenticationException) {
            abort(403);
        }

        return $this->activeDepartmentService->determineActiveDepartment($user);
    }
}
