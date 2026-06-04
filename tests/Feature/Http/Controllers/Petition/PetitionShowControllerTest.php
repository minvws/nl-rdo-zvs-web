<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\PetitionVariant;
use App\Enums\ProcessingStepStatus;
use App\Enums\RouteName;
use App\Enums\TermType;
use App\Enums\TimelineFilterGroup;
use App\Enums\TimelineType;
use App\Models\Decision;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionTerm;
use App\Models\PetitionType;
use App\Models\ProcessingStep;
use App\Models\TimelineItem;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function __;
use function array_keys;
use function config;

class PetitionShowControllerTest extends FeatureTestCase
{
    public function testShow(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition->id,
            ])
            ->assertOk()
            ->assertViewIs('petition.show');
    }

    public function testShowIntentionallyDisplaysCrossDepartmentRelations(): void
    {
        $departmentA = Department::factory()->create(['name' => 'Department A']);
        $departmentB = Department::factory()->create(['name' => 'Department B']);

        $petitionInDepartmentA = Petition::factory()->recycle($departmentA)->create();
        Petition::factory()->recycle($departmentB)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($departmentB, Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser, true, $departmentB)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $departmentA->slug,
                'petition' => $petitionInDepartmentA->id,
            ])
            ->assertOk()
            ->assertViewIs('petition.show');
    }

    public function testShowWithDeadlineableTerm(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()
            ->recycle($department)
            ->create([
                'type' => $this->faker->randomElement([
                    PetitionVariant::BEZWAAR,
                    PetitionVariant::WOO_VERZOEK,
                ]),
            ]);
        $petition = Petition::factory()->recycle($department)->recycle($petitionType)->create();
        PetitionTerm::factory()->recycle($petition)->create([
            'type' => TermType::FIRST->value,
        ]);
        PetitionTerm::factory()->recycle($petition)->create([
            'type' => TermType::SUSPENSION->value,
        ]);
        PetitionTerm::factory()->recycle($petition)->create([
            'type' => TermType::PENALTY->value,
            'start_date' => CalendarDate::today()->subDay(),
            'duration_in_days' => 10,
            'penalty_amount_in_euros' => 100,
        ]);

        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition->id,
            ])
            ->assertOk()
            ->assertViewIs('petition.show')
            ->assertSee(__('term.overview'));
    }

    public function testShowWithTimelineFilter(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        TimelineItem::factory()->recycle($petition)->create([
            'type' => TimelineType::NOTE->value,
            'data' => [
                'comment' => 'Test note comment',
                'attachmentIds' => [],
            ],
        ]);

        TimelineItem::factory()->recycle($petition)->create([
            'type' => TimelineType::TIMELINEABLE_CREATED->value,
            'data' => [],
        ]);

        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $response = $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition,
                'timeline_filter_group' => TimelineFilterGroup::NOTES->value,
            ])
            ->assertOk()
            ->assertViewIs('petition.show')
            ->assertViewHas('selectedTimelineFilter', TimelineFilterGroup::NOTES->value)
            ->assertViewHas('timelineFilterGroups');

        $timelineFilterGroups = $response->viewData('timelineFilterGroups');
        $this->assertIsArray($timelineFilterGroups);
        $this->assertCount(2, $timelineFilterGroups);
        $this->assertEqualsCanonicalizing(
            [
                TimelineFilterGroup::NOTES->value,
                TimelineFilterGroup::UPDATES->value,
            ],
            array_keys($timelineFilterGroups),
        );
    }

    public function testShowWithTimelineFilterActuallyFiltersItems(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        TimelineItem::factory()->recycle($petition)->create([
            'type' => TimelineType::NOTE->value,
            'data' => [
                'comment' => 'Test note comment',
                'attachmentIds' => [],
            ],
        ]);

        TimelineItem::factory()->recycle($petition)->create([
            'type' => TimelineType::TIMELINEABLE_CREATED->value,
            'data' => [],
        ]);

        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();

        $response = $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition,
                'timeline_filter_group' => TimelineFilterGroup::NOTES->value,
            ]);

        $response->assertStatus(200);

        $allowedTypes = config('timeline_filters.groups.' . TimelineFilterGroup::NOTES->value);
        $this->assertContains(TimelineType::NOTE->value, $allowedTypes);
        $this->assertNotContains(TimelineType::STATUS_OCCURRENCE->value, $allowedTypes);
    }

    public function testShowWithNoSelectionTimelineFilter(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();

        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition,
                'timeline_filter_group' => 'no_selection',
            ])
            ->assertOk()
            ->assertViewIs('petition.show')
            ->assertViewHas('selectedTimelineFilter', 'no_selection')
            ->assertViewHas('timelineFilterGroups');
    }

    public function testShowWithoutTimelineFilter(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        TimelineItem::factory()->recycle($petition)->create([
            'type' => TimelineType::NOTE->value,
            'data' => [
                'comment' => 'Test note comment',
                'attachmentIds' => [],
            ],
        ]);

        TimelineItem::factory()->recycle($petition)->create([
            'type' => TimelineType::TIMELINEABLE_CREATED->value,
            'data' => [],
        ]);

        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $response = $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk()
            ->assertViewIs('petition.show')
            ->assertViewHas('selectedTimelineFilter', 'no_selection')
            ->assertViewHas('timelineFilterGroups');

        $timelineFilterGroups = $response->viewData('timelineFilterGroups');
        $this->assertIsArray($timelineFilterGroups);
        $this->assertCount(2, $timelineFilterGroups);
        $this->assertEqualsCanonicalizing(
            [
                TimelineFilterGroup::NOTES->value,
                TimelineFilterGroup::UPDATES->value,
            ],
            array_keys($timelineFilterGroups),
        );
    }

    #[Test]
    public function testPetitionCrossDepartmentVulnerability(): void
    {
        $departmentA = Department::factory()->create(['name' => 'Department A']);
        $departmentB = Department::factory()->create(['name' => 'Department B']);

        $petitionFromDepartmentA = Petition::factory()
            ->recycle($departmentA)
            ->create(['name' => 'Secret Petition A']);

        $userFromDepartmentB = User::factory()
            ->withPermissionsAndDepartment($departmentB, Permission::PETITION_READ)
            ->fullyVerified()
            ->create();

        $response = $this->beUser($userFromDepartmentB, true, $departmentB)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $departmentB->slug,
                'petition' => $petitionFromDepartmentA->id,
            ]);

        $response->assertNotFound();
    }

    #[Test]
    public function testShowDecisionTableDisplaysProcessingStepsInProgress(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $decision = Decision::factory()->recycle($department)->create();
        $petition->decisions()->attach($decision);

        // Create processing steps with different statuses
        ProcessingStep::factory()->recycle($decision)->create([
            'name' => 'Step 1 Pending',
            'status' => ProcessingStepStatus::PENDING,
            'ordering' => 1,
        ]);
        ProcessingStep::factory()->recycle($decision)->create([
            'name' => 'Step 2 Closed',
            'status' => ProcessingStepStatus::CLOSED,
            'ordering' => 2,
        ]);
        ProcessingStep::factory()->recycle($decision)->create([
            'name' => 'Step 3 Pending',
            'status' => ProcessingStepStatus::PENDING,
            'ordering' => 3,
        ]);

        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition->id,
            ])
            ->assertOk()
            ->assertViewIs('petition.show')
            ->assertSee(__('decision.processing_steps_completed'))
            ->assertSee(__('decision.processing_steps_in_progress'))
            ->assertSee('Step 1 Pending, Step 3 Pending');
    }

    #[Test]
    public function testShowDecisionTableDisplaysDashWhenNoProcessingStepsInProgress(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $decision = Decision::factory()->recycle($department)->create();
        $petition->decisions()->attach($decision);

        // Create only closed processing steps
        ProcessingStep::factory()->recycle($decision)->create([
            'name' => 'Step 1 Closed',
            'status' => ProcessingStepStatus::CLOSED,
            'ordering' => 1,
        ]);
        ProcessingStep::factory()->recycle($decision)->create([
            'name' => 'Step 2 Closed',
            'status' => ProcessingStepStatus::CLOSED,
            'ordering' => 2,
        ]);

        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition->id,
            ])
            ->assertOk()
            ->assertViewIs('petition.show')
            ->assertSee(__('decision.processing_steps_completed'))
            ->assertSee(__('decision.processing_steps_in_progress'))
            ->assertSeeInOrder([$decision->date->format('d-m-Y'), '-', '2/2']);
    }
}
