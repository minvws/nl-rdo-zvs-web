<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\AssignmentRole;
use App\Enums\Authorization\Permission;
use App\Enums\NotificationRuleAnchor;
use App\Enums\PetitionEventType;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\NotificationRule;
use App\Models\Petition;
use App\Models\PetitionAssignment;
use App\Models\PetitionEvent;
use App\Models\User;
use App\Notifications\NotificationRuleTriggered;
use App\Services\NotificationRule\NotificationRuleEvaluator;
use App\ValueObjects\CalendarDate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Mockery;
use RuntimeException;
use Tests\Feature\FeatureTestCase;

use function sprintf;

class PetitionAssignedUserControllerTest extends FeatureTestCase
{
    public function testEditAssignedUser(): void
    {
        $department = Department::factory()
            ->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_PRIMARY_EDIT, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk()
            ->assertViewIs('form');
    }

    public function testEditAssignedUserNotFound(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_PRIMARY_EDIT, [
                'department' => $department,
                'petition' => $this->faker->uuid(),
            ])
            ->assertNotFound();
    }

    public function testViewAssignedUser(): void
    {
        $department = Department::factory()
            ->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_PRIMARY_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk();
    }

    public function testUpdateAssignedUser(): void
    {
        Notification::fake();

        $department = Department::factory()
            ->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();
        $user = User::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_PRIMARY_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'user_id' => $user->id->toString(),
            ])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition->id,
            ]);

        $petition->refresh();
        $this->assertEquals($user->id, $petition->firstAssignee->user_id);
        Notification::assertNothingSent();
    }

    public function testUpdateAssignedUserSendsTheNotificationsThatAreAlreadyDue(): void
    {
        Notification::fake();

        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        PetitionEvent::factory()->for($petition)->withType(PetitionEventType::ACTUAL_DISCLOSURE)->create([
            'date' => CalendarDate::today(),
        ]);
        NotificationRule::factory()->recycle($department)->create([
            'anchor' => NotificationRuleAnchor::EVENT_DATE,
            'event_type' => PetitionEventType::ACTUAL_DISCLOSURE,
            'offset_days' => 0,
            'active_from' => CalendarDate::today(),
        ]);
        $user = User::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_PRIMARY_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'user_id' => $user->id->toString(),
            ])
            ->assertRedirect();

        Notification::assertSentTo($user, NotificationRuleTriggered::class);
    }

    public function testUpdateAssignedUserSavesTheAssignmentWhenNotificationRulesFail(): void
    {
        Log::spy();
        $evaluator = Mockery::mock(NotificationRuleEvaluator::class);
        $evaluator->shouldReceive('evaluate')->once()->andThrow(new RuntimeException('mail server down'));
        $this->app->instance(NotificationRuleEvaluator::class, $evaluator);

        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $user = User::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_PRIMARY_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'user_id' => $user->id->toString(),
            ])
            ->assertRedirect();

        $this->assertEquals($user->id, $petition->refresh()->firstAssignee?->user_id);
        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(
                static fn(string $message): bool => $message === sprintf(
                    'Failed to evaluate notification rules for petition ID: %s',
                    $petition->id,
                ),
            );
    }

    public function testUpdateAssignedUserAllowsAssignedUserToBeNull(): void
    {
        $department = Department::factory()
            ->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_PRIMARY_EDIT, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'user_id' => null,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition->id,
            ]);

        $petition->refresh();
        $this->assertNull($petition->firstAssignee);
    }

    public function testUpdateAssignedUserWithHtmx(): void
    {
        $department = Department::factory()
            ->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();
        $user = User::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRouteAsHtmx(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_PRIMARY_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'user_id' => $user->id->toString(),
            ])
            ->assertOk();

        $petition->refresh();
        $this->assertEquals($user->id, $petition->firstAssignee->user_id);
    }

    public function testUpdateAssignedUserToNullWithHtmxShowsEmptyState(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $assignedUser = User::factory()->recycle($department)->create();

        PetitionAssignment::factory()->create([
            'petition_id' => $petition->id,
            'user_id' => $assignedUser->id,
            'assignment_role' => AssignmentRole::PRIMARY,
        ]);

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRouteAsHtmx(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_PRIMARY_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'user_id' => null,
            ])
            ->assertOk()
            ->assertDontSee($assignedUser->name)
            ->assertSee('-');

        $petition->refresh();
        $this->assertNull($petition->firstAssignee);
    }

    public function testUpdateAssignedUserNotFound(): void
    {
        $department = Department::factory()->create();
        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_PRIMARY_UPDATE, [
                'department' => $department,
                'petition' => $this->faker->uuid(),
            ], [
                'user_id' => $this->faker->uuid()->toString(),
            ])
            ->assertNotFound();
    }

    public function testUpdateAssignedUserNotFoundWithHtmx(): void
    {
        $department = Department::factory()->create();
        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRouteAsHtmx(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_PRIMARY_UPDATE, [
                'department' => $department,
                'petition' => $this->faker->uuid(),
            ], [
                'hx-target' => 'hx-target',
                'user_id' => $this->faker->uuid()->toString(),
            ])
            ->assertNotFound();
    }

    public function testUpdateAssignedUserIdInvalidWithHtmx(): void
    {
        $department = Department::factory()
            ->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRouteAsHtmx(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_PRIMARY_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'hx-target' => 'hx-target',
                'user_id' => $this->faker->word(),
            ])
            ->assertSessionHasErrors([
                'user_id' => 'Vul een geldig uuid in',
            ]);
    }

    public function testUpdateAssignedUserPetitionNotFound(): void
    {
        $department = Department::factory()
            ->create();
        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_PRIMARY_UPDATE, [
                'department' => $department,
                'petition' => $this->faker->uuid(),
            ], [
                'user_id' => null,
            ])
            ->assertNotFound();
    }

    public function testUpdateAssignedUserWithoutPermission(): void
    {
        $department = Department::factory()
            ->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();
        $user = User::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_PRIMARY_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'user_id' => $user->id->toString(),
            ])
            ->assertForbidden();

        $petition->refresh();
    }
}
