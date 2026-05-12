<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Decision;

use App\Enums\Authorization\Permission;
use App\Enums\DecisionType;
use App\Enums\RouteName;
use App\Models\Decision;
use App\Models\Department;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class DecisionReferenceUniquenessTest extends FeatureTestCase
{
    #[Test]
    public function cannotCreateDecisionWithExistingReferenceDifferentCase(): void
    {
        $department = Department::factory()->create();
        Decision::factory()->recycle($department)->create(['reference' => 'Abc']);

        $authUser = User::factory()
            ->withPermissions(Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_STORE, [
                'department' => $department,
            ], [
                'name' => $this->faker->name,
                'reference' => 'abc',
                'type' => DecisionType::REGULAR->value,
            ])
            ->assertSessionHasErrors('reference');
    }

    #[Test]
    public function cannotCreateDecisionWithExistingReferenceSameCase(): void
    {
        $department = Department::factory()->create();
        Decision::factory()->recycle($department)->create(['reference' => 'abc']);

        $authUser = User::factory()
            ->withPermissions(Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_STORE, [
                'department' => $department,
            ], [
                'name' => $this->faker->name,
                'reference' => 'abc',
                'type' => DecisionType::REGULAR->value,
            ])
            ->assertSessionHasErrors('reference');
    }

    #[Test]
    public function storesReferenceInLowercase(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()
            ->withPermissions(Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_STORE, [
                'department' => $department,
            ], [
                'name' => $this->faker->name,
                'reference' => 'ABC-123',
                'type' => DecisionType::REGULAR->value,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(Decision::class, [
            'reference' => 'abc-123',
        ]);
    }

    #[Test]
    public function canUpdateOwnReferenceToDifferentCase(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create(['reference' => 'abc']);

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department, Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_UPDATE, [
                'department' => $department,
                'decision' => $decision,
            ], [
                'name' => $decision->name,
                'reference' => 'ABC',
            ])
            ->assertSessionHasNoErrors();

        $decision->refresh();
        $this->assertEquals('abc', $decision->reference);
    }

    #[Test]
    public function cannotUpdateReferenceToExistingReferenceFromAnotherDecision(): void
    {
        $department = Department::factory()->create();
        Decision::factory()->recycle($department)->create(['reference' => 'existing-ref']);
        $decision = Decision::factory()->recycle($department)->create(['reference' => 'other-ref']);

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department, Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_UPDATE, [
                'department' => $department,
                'decision' => $decision,
            ], [
                'name' => $decision->name,
                'reference' => 'EXISTING-REF',
            ])
            ->assertSessionHasErrors('reference');
    }

    #[Test]
    public function canCreateDecisionWithUniqueReference(): void
    {
        $department = Department::factory()->create();
        Decision::factory()->recycle($department)->create(['reference' => 'existing-ref']);

        $authUser = User::factory()
            ->withPermissions(Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_STORE, [
                'department' => $department,
            ], [
                'name' => $this->faker->name,
                'reference' => 'unique-ref',
                'type' => DecisionType::REGULAR->value,
            ])
            ->assertSessionHasNoErrors();
    }
}
