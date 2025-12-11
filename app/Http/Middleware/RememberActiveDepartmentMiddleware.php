<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\DepartmentNotInRouteException;
use App\Models\Department;
use App\Models\User;
use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\Assert\Assert;

use function abort_unless;
use function assert;

class RememberActiveDepartmentMiddleware
{
    public function __construct(private readonly AuthManager $authManager)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): Response $next
     *
     * @throws DepartmentNotInRouteException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        assert($route instanceof Route);

        if (!$route->hasParameter('department')) {
            throw new DepartmentNotInRouteException();
        }

        $department = $route->parameter('department');
        Assert::isInstanceOf($department, Department::class);

        $user = $this->authManager->user();
        abort_unless($this->authManager->check(), 403);
        Assert::isInstanceOf($user, User::class);

        $user->timestamps = false;
        $user->last_visited_department_id = $department->id->toString();
        $user->save();

        return $next($request);
    }
}
