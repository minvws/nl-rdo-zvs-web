<?php

declare(strict_types=1);

namespace App\Http\Controllers\Authentication;

use App\Actions\PasswordResetToken\ForgotPasswordMailAction;
use App\Enums\RouteName;
use App\Events\ForgotPasswordEvent;
use App\Http\NotFoundException;
use App\Http\Requests\Authentication\Password\ForgotPasswordStoreRequest;
use App\Http\Requests\Authentication\Password\PasswordStoreRequest;
use App\Http\Requests\Authentication\Password\ResetPasswordStoreRequest;
use App\Http\Requests\FormRequest;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Repositories\DatabaseRepositoryTransaction;
use App\Repositories\RepositoryTransactionException;
use App\Services\Authentication\AuthenticationException;
use App\Services\Authentication\AuthenticationServiceInterface;
use App\Services\HashService;
use App\Services\RateLimit\RateLimitExceededException;
use App\Services\RateLimit\RateLimitService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use InvalidArgumentException;
use Ramsey\Uuid\UuidInterface;

use function __;
use function event;

final readonly class PasswordController
{
    public function __construct(
        private AuthenticationServiceInterface $authenticationService,
        private DatabaseRepositoryTransaction $databaseRepositoryTransaction,
        private RateLimitService $rateLimitService,
        private Redirector $redirector,
        private Factory $view,
        private HashService $hashService,
        private Dispatcher $dispatcher,
    ) {
    }

    public function forgotPassword(): View
    {
        return $this->view->make('authentication.forgot-password');
    }

    /**
     * @throws RateLimitExceededException
     */
    public function forgotPasswordMail(
        ForgotPasswordStoreRequest $request,
        ForgotPasswordMailAction $action,
    ): RedirectResponse {
        $email = $request->getString('email');
        event(new ForgotPasswordEvent($email));
        $this->rateLimitService->check($request, $email);

        $user = User::query()->where('email', $email)->first();
        if ($user !== null) {
            $action->execute($user);
        }

        return $this->redirector->route(RouteName::LOGIN)
            ->with('status', __('authentication.verify_email.verification_sent'));
    }

    public function reset(FormRequest $request): View|RedirectResponse
    {
        if (!$request->has('id') || !$request->has('token')) {
            return $this->redirector->route(RouteName::LOGIN);
        }

        try {
            $id = $request->getUuid('id');
            $token = $request->getString('token');
        } catch (InvalidArgumentException) {
            throw new NotFoundException();
        }

        return $this->view->make('authentication.reset-password', [
            'id' => $id,
            'email' => $this->getByIdAndToken($id, $token)->email,
            'token' => $token,
        ]);
    }

    /**
     * @throws RateLimitExceededException
     */
    public function resetAttempt(ResetPasswordStoreRequest $request): RedirectResponse
    {
        try {
            $id = $request->getUuid('id');
            $token = $request->getString('token');
        } catch (InvalidArgumentException) {
            throw new NotFoundException();
        }

        $this->rateLimitService->check($request, $id->toString());

        $passwordResetToken = $this->getByIdAndToken($id, $token);
        $newPassword = $request->getString('password');

        try {
            $this->databaseRepositoryTransaction->transaction(function () use ($passwordResetToken, $newPassword): void {
                $user = User::query()->where(['email' => $passwordResetToken->email])->firstOrFail();
                $user->update([
                    'password' => $this->hashService->hash($newPassword),
                    'email_verified_at' => CarbonImmutable::now(),
                ]);
                $passwordResetToken->delete();
            });
        } catch (RepositoryTransactionException $repositoryTransactionException) {
            throw NotFoundException::fromThrowable('Password update failed', $repositoryTransactionException);
        }

        return $this->redirector->route(RouteName::LOGIN)
            ->with('status', __('user.password_reset_success'));
    }

    /**
     * @throws AuthenticationException
     */
    public function update(PasswordStoreRequest $request): RedirectResponse
    {
        $user = $this->authenticationService->user();

        $user->update([
            'password' => $this->hashService->hash($request->getString('password')),
        ]);

        $this->dispatcher->dispatch(new PasswordReset($user));

        $this->authenticationService->logoutEverywhere();

        return $this->redirector->route(RouteName::LOGIN)
            ->with('status', 'password-updated');
    }

    private function getByIdAndToken(UuidInterface $id, string $token): PasswordResetToken
    {
        return PasswordResetToken::query()->where('id', $id)
            ->where('token', $token)
            ->where('created_at', '>', CarbonImmutable::now()->subHour())
            ->firstOrFail();
    }
}
