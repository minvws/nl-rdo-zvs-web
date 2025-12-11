<?php

declare(strict_types=1);

namespace Tests\Feature\Facades;

use App\Facades\ActiveDepartment;
use App\Models\Department;
use App\Services\Authorisation\ActiveDepartmentService;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class ActiveDepartmentTest extends FeatureTestCase
{
    #[Test]
    public function testHasActiveDepartment(): void
    {
        $hasActiveDepartment = $this->faker->boolean();

        $this->mock(ActiveDepartmentService::class, static function (MockInterface $mock) use ($hasActiveDepartment): void {
            $mock->expects('hasActiveDepartment')
                ->andReturn($hasActiveDepartment);
        });

        $this->assertSame($hasActiveDepartment, ActiveDepartment::hasActiveDepartment());
    }

    #[Test]
    public function testGetActiveDepartment(): void
    {
        $department = new Department();

        $this->mock(ActiveDepartmentService::class, static function (MockInterface $mock) use ($department): void {
            $mock->expects('getActiveDepartment')
                ->andReturn($department);
        });

        $this->assertSame($department, ActiveDepartment::getActiveDepartment());
    }
}
