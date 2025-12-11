<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\RememberActiveDepartmentMiddleware;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Feature\FeatureTestCase;

class RememberActiveDepartmentMiddlewareTest extends FeatureTestCase
{
    public function testHandleUpdatesLastVisitedDepartment(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();

        $this->actingAs($user);

        $request = new Request();
        $request->setRouteResolver(function () use ($department) {
            $route = new Route('GET', '/test', []);
            $route->parameters = ['department' => $department];
            return $route;
        });

        $middleware = $this->app->make(RememberActiveDepartmentMiddleware::class);

        $response = $middleware->handle($request, function () {
            return new Response();
        });

        $this->assertSame(200, $response->getStatusCode());

        $user->refresh();

        $this->assertSame($department->id->toString(), $user->last_visited_department_id->toString());
    }

    public function testHandleThrowsExceptionWhenUserNotAuthenticated(): void
    {
        $department = Department::factory()->create();

        $request = new Request();
        $request->setRouteResolver(function () use ($department) {
            $route = new Route('GET', '/test', []);
            $route->parameters = ['department' => $department];
            return $route;
        });

        $middleware = $this->app->make(RememberActiveDepartmentMiddleware::class);

        $this->expectException(HttpException::class);

        $middleware->handle($request, function () {
            return new Response();
        });
    }
}
