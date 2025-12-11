<?php

declare(strict_types=1);

use App\Config\Config;
use App\Enums\RouteName;
use App\Http\Middleware\EmailAddressVerified;
use App\Http\Middleware\OneTimePasswordAuthenticated;
use App\Http\Middleware\OneTimePasswordNotEnabled;
use App\Http\Middleware\StripTagsMiddleware;
use App\Services\Authentication\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Middleware\ValidatePostSize;
use Illuminate\Http\RedirectResponse;
use Spatie\Csp\AddCspHeaders;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\Assert\Assert;

return Application::configure(basePath: dirname(__DIR__))
    ->withExceptions(static function (Exceptions $exceptions): void {
        $exceptions->respond(static function (Response $response) {
            if ($response->getStatusCode() === 413) {
                return back()->withErrors([
                    'message' => 'validation.upload_too_big',
                ]);
            }

            return $response;
        });
        $exceptions->render(static function (AuthenticationException $authenticationException): RedirectResponse {
            return response()->redirectToRoute(RouteName::LOGIN);
        });
    })
    ->withEvents([
        sprintf('%s/../app/Listeners', __DIR__),
    ])
    ->withMiddleware(static function (Middleware $middleware): void {
        $middleware->remove(ValidatePostSize::class);
        $middleware->web([ValidatePostSize::class]);
        $middleware->trustHosts(static function (): array {
            $trustedHosts = Config::string('app.trusted_hosts');
            $trustedHosts = explode(',', $trustedHosts);
            Assert::isList($trustedHosts);

            return $trustedHosts;
        });
        $middleware->web([
            AddCspHeaders::class,
            StripTagsMiddleware::class,
        ]);
        $middleware->priority([
            ValidatePostSize::class,
            ValidateCsrfToken::class,
        ]);
        $middleware->alias([
            'email-address-verified' => EmailAddressVerified::class,
            'one-time-password-authenticated' => OneTimePasswordAuthenticated::class,
            'one-time-password-not-enabled' => OneTimePasswordNotEnabled::class,
        ]);

        $middleware->group('fully-authenticated', [
            'auth',
            'auth.session',
            'email-address-verified',
            'one-time-password-not-enabled',
            'one-time-password-authenticated',
        ]);
    })
    ->withRouting(web: __DIR__ . '/../routes/web.php', api: __DIR__ . '/../routes/api.php')
    ->create();
