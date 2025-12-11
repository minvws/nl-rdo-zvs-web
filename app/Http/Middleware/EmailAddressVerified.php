<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Authentication\AuthenticationException;
use App\Services\User\UserService;
use Closure;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

readonly class EmailAddressVerified
{
    public function __construct(
        private LoggerInterface $logger,
        private Redirector $redirector,
        private UserService $userService,
        #[CurrentUser]
        private User $user,
    ) {
    }

    /**
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->userService->hasVerifiedEmail($this->user)) {
            return $next($request);
        }

        $this->logger->debug('email not verified');

        return $this->redirector->route('verification.notice');
    }
}
