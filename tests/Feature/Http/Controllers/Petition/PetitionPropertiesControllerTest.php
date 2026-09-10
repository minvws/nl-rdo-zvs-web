<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\OptionalFormFieldSetting;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\ConfigHelper;

use function __;
use function sprintf;

class PetitionPropertiesControllerTest extends FeatureTestCase
{
    public function testEdit(): void
    {
        $department = Department::factory()
            ->create();
        $petition = Petition::factory()->recycle($department)
            ->create();

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();
        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_EDIT, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk()
            ->assertViewIs('form');
    }

    public function testEditMustHaveValidUuidInRoute(): void
    {
        $department = Department::factory()
            ->create();
        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_EDIT, [
                'department' => $department,
                'petition' => $this->faker()->word(),
            ])
            ->assertStatus(404);
    }

    public function testEditNotFound(): void
    {
        $authUser = User::factory()->withPermissionsAndDepartment(null, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, null)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_EDIT, [
                'department' => $this->faker->slug(),
                'petition' => $this->faker->uuid(),
            ])
            ->assertNotFound();
    }

    public function testShow(): void
    {
        $department = Department::factory()
            ->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissionsAndDepartment(
            $petition->department,
            Permission::PETITION_READ,
        )->fullyVerified()->create();
        $this->beUser($authUser, true, $petition->department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk()
            ->assertViewIs('petition.properties.show');
    }

    public function testShowForNonExisting(): void
    {
        $department = Department::factory()
            ->create();
        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_SHOW, [
                'department' => $department,
                'petition' => $this->faker->uuid(),
            ])
            ->assertNotFound();
    }

    public function testUpdate(): void
    {
        $department = Department::factory()
            ->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $name = $this->faker->name();
        $dateOfEntry = $this->faker->calendarDate();
        $dateAppealedDecision = $this->faker->calendarDate();
        $description = $this->faker->optional()->text();

        ConfigHelper::set(sprintf('petition_variant.%s.optional_form_fields', $petition->petitionType->type->value), [
            'name' => OptionalFormFieldSetting::REQUIRED,
            'description' => OptionalFormFieldSetting::OPTIONAL,
            'date_appealed_decision' => OptionalFormFieldSetting::OPTIONAL,
            'petition_category_id' => OptionalFormFieldSetting::EXCLUDED,
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'name' => $name,
                'date_of_entry' => $dateOfEntry->format('Y-m-d'),
                'description' => $description,
                'date_appealed_decision' => $dateAppealedDecision->format('Y-m-d'),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(Petition::class, [
            'name' => $name,
            'date_of_entry' => $dateOfEntry->format('Y-m-d'),
            'date_appealed_decision' => $dateAppealedDecision->format('Y-m-d'),
            'description' => $description,
        ]);
    }

    public function testUpdateWithDisabledFields(): void
    {
        $department = Department::factory()
            ->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $dateOfEntry = $this->faker->calendarDate();

        ConfigHelper::set(sprintf('petition_variant.%s.optional_form_fields', $petition->petitionType->type->value), [
            'name' => OptionalFormFieldSetting::EXCLUDED,
            'description' => OptionalFormFieldSetting::EXCLUDED,
            'date_appealed_decision' => OptionalFormFieldSetting::EXCLUDED,
            'petition_category_id' => OptionalFormFieldSetting::REQUIRED,
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'date_of_entry' => $dateOfEntry->format('Y-m-d'),
                'petition_category_id' => $petition->petition_category_id->toString(),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(Petition::class, [
            'name' => $petition->petitionCategory->name,
            'date_of_entry' => $dateOfEntry->format('Y-m-d'),
            'description' => $petition->description,
        ]);
    }

    public function testUpdateWithErrorsAndHxTarget(): void
    {
        $department = Department::factory()
            ->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();
        $hxTarget = $this->faker->slug(1);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'hx-target' => $hxTarget,
            ])
            ->assertSessionHasErrors('date_of_entry')
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_EDIT, [
                'department' => $department,
                'petition' => $petition,
                'hx-target' => $hxTarget,
            ]);
    }

    public function testUpdateWithHtmx(): void
    {
        $department = Department::factory()
            ->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();
        $name = $this->faker->word();

        ConfigHelper::set(sprintf('petition_variant.%s.optional_form_fields', $petition->petitionType->type->value), [
            'name' => OptionalFormFieldSetting::REQUIRED,
            'description' => OptionalFormFieldSetting::OPTIONAL,
            'date_appealed_decision' => OptionalFormFieldSetting::OPTIONAL,
            'petition_category_id' => OptionalFormFieldSetting::EXCLUDED,
        ]);

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE, Permission::PETITION_READ)
            ->fullyVerified()
            ->create();
        $this->beUser($authUser, true, $department)
            ->postByRouteAsHtmx(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'name' => $name,
                'date_of_entry' => $petition->date_of_entry->format('Y-m-d'),
                'description' => $petition->description,
            ])
            ->assertViewIs('petition.properties.show');

        $petition->refresh();
        $this->assertEquals($name, $petition->name);
    }

    public function testUpdateWithHtmxForNonExisting(): void
    {
        $department = Department::factory()
            ->create();
        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRouteAsHtmx(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_UPDATE, [
                'department' => $department,
                'petition' => $this->faker->uuid(),
            ], [
                'name' => $this->faker->name(),
                'date_of_entry' => $this->faker->calendarDate()->format('Y-m-d'),
                'description' => $this->faker->sentence(),
            ])
            ->assertNotFound();
    }

    public function testUpdateStoresTheDeadlineOverride(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $this->configureOptionalFormFields($petition);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'name' => $this->faker->name(),
                'date_of_entry' => $petition->date_of_entry->format('Y-m-d'),
                'deadline_override_at' => '2026-06-30',
                'deadline_override_reason' => 'Afspraak met verzoeker',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $petition->refresh();

        $this->assertSame('2026-06-30', $petition->deadline_override_at->format('Y-m-d'));
        $this->assertSame('Afspraak met verzoeker', $petition->deadline_override_reason);
    }

    public function testUpdateClearsTheReasonWhenTheOverrideDateIsEmptied(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create([
            'deadline_override_at' => '2026-06-30',
            'deadline_override_reason' => 'Afspraak met verzoeker',
        ]);

        $this->configureOptionalFormFields($petition);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'name' => $this->faker->name(),
                'date_of_entry' => $petition->date_of_entry->format('Y-m-d'),
                'deadline_override_at' => '',
                'deadline_override_reason' => 'Afspraak met verzoeker',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $petition->refresh();

        $this->assertNull($petition->deadline_override_at);
        $this->assertNull($petition->deadline_override_reason);
    }

    public function testUpdateRejectsAnOverrideDateWithoutAReason(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create([
            'deadline_override_at' => null,
            'deadline_override_reason' => null,
        ]);

        $this->configureOptionalFormFields($petition);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'name' => $this->faker->name(),
                'date_of_entry' => $petition->date_of_entry->format('Y-m-d'),
                'deadline_override_at' => '2026-06-30',
                'deadline_override_reason' => '',
            ])
            ->assertSessionHasErrors('deadline_override_reason');

        $petition->refresh();

        $this->assertNull($petition->deadline_override_at);
    }

    public function testShowRendersTheOverrideAlongsideTheCalculatedDeadline(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create([
            'deadline_at' => '2026-03-01',
            'deadline_override_at' => '2026-06-30',
            'deadline_override_reason' => 'Afspraak met verzoeker',
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment(
            $petition->department,
            Permission::PETITION_READ,
        )->fullyVerified()->create();

        $this->beUser($authUser, true, $petition->department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk()
            ->assertSee('30-06-2026')
            ->assertSee('Afspraak met verzoeker')
            ->assertSee('01-03-2026');
    }

    public function testShowRendersOnlyTheCalculatedDeadlineWithoutAnOverride(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create([
            'deadline_at' => '2026-03-01',
            'deadline_override_at' => null,
            'deadline_override_reason' => null,
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment(
            $petition->department,
            Permission::PETITION_READ,
        )->fullyVerified()->create();

        $this->beUser($authUser, true, $petition->department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk()
            ->assertSee('01-03-2026')
            ->assertDontSee(__('petition.deadline_calculated'));
    }

    private function configureOptionalFormFields(Petition $petition): void
    {
        ConfigHelper::set(sprintf('petition_variant.%s.optional_form_fields', $petition->petitionType->type->value), [
            'name' => OptionalFormFieldSetting::REQUIRED,
            'description' => OptionalFormFieldSetting::OPTIONAL,
            'date_appealed_decision' => OptionalFormFieldSetting::OPTIONAL,
            'petition_category_id' => OptionalFormFieldSetting::EXCLUDED,
        ]);
    }
}
