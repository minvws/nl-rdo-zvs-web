<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RouteName;
use App\Models\Department;
use App\Models\User;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use PHPUnit\Framework\Attributes\Test;

use function departmentRoute;
use function route;

class HelpersTest extends FeatureTestCase
{
    #[Test]
    public function testDepartmentRoute(): void
    {
        $department = Department::factory()->create();

        $this->actingAs(User::factory()->make([
            'last_visited_department_id' => $department->id,
        ]));

        $expectedRoute = route(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $department]);
        $departmentRoute = departmentRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX);

        $this->assertEquals($expectedRoute, $departmentRoute);
    }

    #[Test]
    public function testDepartmentRouteIfDepartmentNotInRoute(): void
    {
        $this->actingAs(User::factory()->create());
        $expectedRoute = route(RouteName::ADMIN_SHOW);
        $departmentRoute = departmentRoute(RouteName::ADMIN_SHOW);

        $this->assertEquals($expectedRoute, $departmentRoute);
    }

    #[Test]
    public function testDepartmentRouteIfActiveDepartmentNotSet(): void
    {
        $this->actingAs(User::factory()->create());
        $this->expectException(UrlGenerationException::class);
        departmentRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX);
    }
}
