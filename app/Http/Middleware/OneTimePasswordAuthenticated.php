<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Authentication\OneTimePasswordService;
use Closure;
use Exception;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

readonly class OneTimePasswordAuthenticated
{
    public function __construct(
        private LoggerInterface $logger,
        private OneTimePasswordService $oneTimePasswordService,
        private Redirector $redirector,
        #[CurrentUser]
        private User $user,
    ) {
    }

    /**
     * @throws Exception
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->oneTimePasswordService->isAuthenticated($this->user)) {
            $this->logger->debug('otp not authenticated');

            return $this->redirector->route('one-time-password.authenticate', [
                'next' => $request->getRequestUri(),
            ]);
        }

        return $next($request);
    }
}
