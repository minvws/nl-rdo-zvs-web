<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RouteName;
use App\Models\Department;
use App\View\HtmxHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Tests\Helpers\Models\UserHelper;
use Tests\TestCase;

use function array_merge;

abstract class FeatureTestCase extends TestCase
{
    use DatabaseTransactions;
    use UserHelper;

    /**
     * @param array<string, string> $routeParameters
     * @param array<string, mixed> $headers
     */
    protected function getByRoute(string|RouteName $route, array $routeParameters = [], array $headers = []): TestResponse
    {
        if ($route instanceof RouteName) {
            $route = $route->value;
        }

        return $this->get(URL::route($route, $routeParameters), $headers);
    }

    /**
     * @param array<string, string> $routeParameters
     * @param array<string, mixed> $headers
     */
    protected function getByRouteAsHtmx(string|RouteName $route, array $routeParameters = [], array $headers = []): TestResponse
    {
        return $this->get(URL::route($route, $routeParameters), $this->addHtmxHeader($headers));
    }

    /**
     * @param array<string, string> $routeParameters
     * @param array<string, mixed> $data
     * @param array<string, mixed> $headers
     */
    protected function postByRoute(string|RouteName $route, array $routeParameters = [], array $data = [], array $headers = []): TestResponse
    {
        if ($route instanceof RouteName) {
            $route = $route->value;
        }

        return $this->post(URL::route($route, $routeParameters), $data, $headers);
    }

    /**
     * @param array<string, string> $routeParameters
     * @param array<string, mixed> $data
     * @param array<string, mixed> $headers
     */
    protected function postByRouteAsHtmx(string|RouteName $route, array $routeParameters = [], array $data = [], array $headers = []): TestResponse
    {
        return $this->postByRoute($route, $routeParameters, $data, $this->addHtmxHeader($headers));
    }

    /**
     * @param array<string, string> $routeParameters
     * @param array<string, mixed> $data
     * @param array<string, mixed> $headers
     */
    protected function putByRoute(string|RouteName $route, array $routeParameters = [], array $data = [], array $headers = []): TestResponse
    {
        return $this->put(URL::route($route, $routeParameters), $data, $headers);
    }

    /**
     * @param array<string, string> $routeParameters
     * @param array<string, mixed> $data
     * @param array<string, mixed> $headers
     */
    protected function deleteByRoute(string|RouteName $route, array $routeParameters = [], array $data = [], array $headers = []): TestResponse
    {
        if ($route instanceof RouteName) {
            $route = $route->value;
        }

        return $this->delete(URL::route($route, $routeParameters), $data, $headers);
    }

    /**
     * @param array<string, string> $routeParameters
     * @param array<string, mixed> $data
     * @param array<string, mixed> $headers
     */
    protected function deleteByRouteAsHtmx(string|RouteName $route, array $routeParameters = [], array $data = [], array $headers = []): TestResponse
    {
        return $this->delete(URL::route($route, $routeParameters), $data, $this->addHtmxHeader($headers));
    }

    protected function setActiveDepartment(Department $department): void
    {
        Session::put('active_department', $department->slug);
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<string, string>
     */
    private function addHtmxHeader(array $headers): array
    {
        return array_merge($headers, [HtmxHelper::HTMX_REQUEST_HEADER => true]);
    }
}
