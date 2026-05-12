<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\PolicyDepartment;

use App\Actions\PolicyDepartment\UpdatePolicyDepartmentAction;
use App\Models\PolicyDepartment;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class UpdatePolicyDepartmentActionTest extends FeatureTestCase
{
    #[Test]
    public function testExecuteUpdatesNameAndActive(): void
    {
        $policyDepartment = PolicyDepartment::factory()->create([
            'name' => 'Original Name',
            'active' => false,
        ]);

        $action = new UpdatePolicyDepartmentAction();
        $data = [
            'name' => 'Updated Name',
            'active' => true,
        ];

        $action->execute($policyDepartment, $data);

        // Refresh the model to get updated values
        $policyDepartment->refresh();

        $this->assertSame('Updated Name', $policyDepartment->name);
        $this->assertTrue($policyDepartment->active);

        $this->assertDatabaseHas(PolicyDepartment::class, [
            'id' => $policyDepartment->id,
            'name' => 'Updated Name',
            'active' => true,
        ]);
    }

    #[Test]
    public function testExecuteUpdatesOnlyName(): void
    {
        $policyDepartment = PolicyDepartment::factory()->create([
            'name' => 'Original Name',
            'active' => true,
        ]);

        $action = new UpdatePolicyDepartmentAction();
        $data = [
            'name' => 'Name Only Update',
        ];

        $action->execute($policyDepartment, $data);

        $policyDepartment->refresh();
        $this->assertSame('Name Only Update', $policyDepartment->name);
        $this->assertTrue($policyDepartment->active); // Should remain unchanged
    }

    #[Test]
    public function testExecuteUpdatesOnlyActive(): void
    {
        $policyDepartment = PolicyDepartment::factory()->create([
            'name' => 'Unchanged Name',
            'active' => true,
        ]);

        $action = new UpdatePolicyDepartmentAction();
        $data = [
            'name' => 'Unchanged Name', // Keep same name
            'active' => false, // Change only active
        ];

        $action->execute($policyDepartment, $data);

        $policyDepartment->refresh();
        $this->assertSame('Unchanged Name', $policyDepartment->name);
        $this->assertFalse($policyDepartment->active);
    }

    #[Test]
    public function testExecuteWithActiveAsString(): void
    {
        $policyDepartment = PolicyDepartment::factory()->create([
            'name' => 'Test Department',
            'active' => false,
        ]);

        $action = new UpdatePolicyDepartmentAction();
        $data = [
            'name' => 'Test Department',
            'active' => '1', // String value should be converted to boolean
        ];

        $action->execute($policyDepartment, $data);

        $policyDepartment->refresh();
        $this->assertTrue($policyDepartment->active);
    }

    #[Test]
    public function testExecuteHandlesEmptyDataGracefully(): void
    {
        $policyDepartment = PolicyDepartment::factory()->create([
            'name' => 'Original Name',
            'active' => true,
        ]);

        $originalName = $policyDepartment->name;
        $originalActive = $policyDepartment->active;

        $action = new UpdatePolicyDepartmentAction();
        $data = []; // Empty data array

        $action->execute($policyDepartment, $data);

        $policyDepartment->refresh();
        // Should not change anything if no data provided
        $this->assertSame($originalName, $policyDepartment->name);
        $this->assertSame($originalActive, $policyDepartment->active);
    }

    #[Test]
    public function testExecuteTogglesActiveStatus(): void
    {
        // Test toggling from active to inactive
        $activeDepartment = PolicyDepartment::factory()->create(['active' => true]);

        $action = new UpdatePolicyDepartmentAction();
        $action->execute($activeDepartment, [
            'name' => $activeDepartment->name,
            'active' => false,
        ]);

        $activeDepartment->refresh();
        $this->assertFalse($activeDepartment->active);

        // Test toggling from inactive to active
        $inactiveDepartment = PolicyDepartment::factory()->create(['active' => false]);

        $action->execute($inactiveDepartment, [
            'name' => $inactiveDepartment->name,
            'active' => true,
        ]);

        $inactiveDepartment->refresh();
        $this->assertTrue($inactiveDepartment->active);
    }

    #[Test]
    public function testExecuteUpdatesPersistsToDatabase(): void
    {
        $policyDepartment = PolicyDepartment::factory()->create([
            'name' => 'Database Test',
            'active' => false,
        ]);

        $action = new UpdatePolicyDepartmentAction();
        $action->execute($policyDepartment, [
            'name' => 'Updated Database Test',
            'active' => true,
        ]);

        // Verify changes are persisted to database
        $this->assertDatabaseHas(PolicyDepartment::class, [
            'id' => $policyDepartment->id,
            'name' => 'Updated Database Test',
            'active' => true,
        ]);

        $this->assertDatabaseMissing(PolicyDepartment::class, [
            'id' => $policyDepartment->id,
            'name' => 'Database Test',
        ]);
    }
}
