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
use Illuminate\Routing\Route;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\Assert\Assert;

readonly class OneTimePasswordNotEnabled
{
    public function __construct(
        private OneTimePasswordService $oneTimePasswordService,
        private LoggerInterface $logger,
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
        $route = $request->route();
        Assert::isInstanceOf($route, Route::class);
        if (!$this->oneTimePasswordService->hasOtpVerified($this->user) && $route->getPrefix() !== '/one-time-password') {
            $this->logger->debug('User otp not yet verified, header back to enrollment page');

            return $this->redirector->route('one-time-password.enroll');
        }

        return $next($request);
    }
}
