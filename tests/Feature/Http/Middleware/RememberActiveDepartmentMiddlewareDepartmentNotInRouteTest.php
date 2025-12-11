<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Actions\DepartmentNotInRouteException;
use App\Http\Middleware\RememberActiveDepartmentMiddleware;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Tests\Feature\FeatureTestCase;

class RememberActiveDepartmentMiddlewareDepartmentNotInRouteTest extends FeatureTestCase
{
    public function testHandleThrowsExceptionWhenRouteHasNoDepartmentParameter(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $request = new Request();
        $request->setRouteResolver(function () {
            $route = new Route('GET', '/test', []);
            // Note: no department parameter set
            return $route;
        });

        $middleware = $this->app->make(RememberActiveDepartmentMiddleware::class);

        $this->expectException(DepartmentNotInRouteException::class);

        $middleware->handle($request, function () {
            return new Response();
        });
    }
}
