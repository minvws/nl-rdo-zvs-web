<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\PolicyDepartment;

use App\Actions\PolicyDepartment\CreatePolicyDepartmentAction;
use App\Models\PolicyDepartment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreatePolicyDepartmentActionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testExecuteCreatesActivePolicyDepartment(): void
    {
        $action = new CreatePolicyDepartmentAction();
        $data = [
            'name' => 'Test Department',
            'active' => true,
        ];

        $policyDepartment = $action->execute($data);

        $this->assertInstanceOf(PolicyDepartment::class, $policyDepartment);
        $this->assertSame('Test Department', $policyDepartment->name);
        $this->assertTrue($policyDepartment->active);
        $this->assertTrue($policyDepartment->exists);

        $this->assertDatabaseHas(PolicyDepartment::class, [
            'name' => 'Test Department',
            'active' => true,
        ]);
    }

    #[Test]
    public function testExecuteCreatesInactivePolicyDepartment(): void
    {
        $action = new CreatePolicyDepartmentAction();
        $data = [
            'name' => 'Inactive Department',
            'active' => false,
        ];

        $policyDepartment = $action->execute($data);

        $this->assertInstanceOf(PolicyDepartment::class, $policyDepartment);
        $this->assertSame('Inactive Department', $policyDepartment->name);
        $this->assertFalse($policyDepartment->active);

        $this->assertDatabaseHas(PolicyDepartment::class, [
            'name' => 'Inactive Department',
            'active' => false,
        ]);
    }

    #[Test]
    public function testExecuteWithActiveAsString(): void
    {
        $action = new CreatePolicyDepartmentAction();
        $data = [
            'name' => 'String Active Department',
            'active' => '1', // String value should be converted to boolean
        ];

        $policyDepartment = $action->execute($data);

        $this->assertTrue($policyDepartment->active);
    }

    #[Test]
    public function testExecuteWithoutActiveFieldDefaultsToTrue(): void
    {
        $action = new CreatePolicyDepartmentAction();
        $data = [
            'name' => 'Default Active Department',
            // No active field provided
        ];

        $policyDepartment = $action->execute($data);

        $this->assertTrue($policyDepartment->active);
    }

    #[Test]
    public function testExecuteUsesProvidedNameAsIs(): void
    {
        $action = new CreatePolicyDepartmentAction();
        $data = [
            'name' => 'Department Name As Provided',
            'active' => true,
        ];

        $policyDepartment = $action->execute($data);

        $this->assertSame('Department Name As Provided', $policyDepartment->name);
        $this->assertTrue($policyDepartment->active);
    }

    #[Test]
    public function testExecuteHandlesEmptyOptionalFields(): void
    {
        $action = new CreatePolicyDepartmentAction();
        $data = [
            'name' => 'Minimal Department',
        ];

        $policyDepartment = $action->execute($data);

        $this->assertSame('Minimal Department', $policyDepartment->name);
        $this->assertTrue($policyDepartment->active); // Should default to true
    }
}
