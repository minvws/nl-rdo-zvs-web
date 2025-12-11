<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\CustomPetitionPropertyType;
use App\Enums\RouteName;
use App\Models\CustomPetitionProperty;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use Tests\Feature\FeatureTestCase;

use function __;
use function array_merge;
use function session;

class PetitionCustomPetitionPropertiesControllerTest extends FeatureTestCase
{
    private Department $department;
    private Petition $petition;
    private User $readUser;
    private User $writeUser;
    private User $noPermissionUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create();
        $this->petition = Petition::factory()->recycle($this->department)->create();
        $this->readUser = User::factory()
            ->withPermissions(Permission::PETITION_READ)
            ->fullyVerified()
            ->create();
        $this->writeUser = User::factory()
            ->withPermissionsAndDepartment($this->department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();
        $this->noPermissionUser = User::factory()->fullyVerified()->create();
    }

    /**
     * Create route parameters for the petition routes.
     *
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function createRouteParams(array $overrides = []): array
    {
        return array_merge([
            'department' => $this->department,
            'petition' => $this->petition,
        ], $overrides);
    }

    private function createCustomPetitionPropertyWithGrouping(int|null $grouping): CustomPetitionProperty
    {
        return CustomPetitionProperty::factory()->create([
            'type' => CustomPetitionPropertyType::OPTION,
            'petition_type_id' => $this->petition->petition_type_id,
            'grouping' => $grouping,
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return Collection<int, CustomPetitionProperty>
     */
    private function createCustomPetitionPropertiesForPetition(int $count = 2, array $overrides = []): Collection
    {
        return CustomPetitionProperty::factory()
            ->count($count)
            ->create(array_merge([
                'petition_type_id' => $this->petition->petition_type_id,
                'type' => CustomPetitionPropertyType::OPTION,
            ], $overrides));
    }

    /**
     * @param array<string> $fieldIds
     */
    private function assertValidationErrors(TestResponse $response, array $fieldIds): void
    {
        $response->assertSessionHasErrors($fieldIds);
        $errors = session('errors');
        $this->assertNotNull($errors);

        foreach ($fieldIds as $fieldId) {
            $this->assertTrue($errors->has($fieldId));
            $this->assertEquals(
                __('validation.unique_custom_petition_property_grouping'),
                $errors->first($fieldId),
            );
        }
    }

    public function testEditCustomPetitionProperties(): void
    {
        $this->beUser($this->writeUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_EDIT, $this->createRouteParams())
            ->assertOk()
            ->assertViewIs('form');
    }

    public function testEditCustomPetitionPropertiesWithoutPermission(): void
    {
        $this->beUser($this->noPermissionUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_EDIT, $this->createRouteParams())
            ->assertForbidden();
    }

    public function testEditCustomPetitionPropertiesWithCheckedOption(): void
    {
        CustomPetitionProperty::factory()->create([
            'type' => CustomPetitionPropertyType::TITLE,
            'petition_type_id' => $this->petition->petition_type_id,
        ]);
        $customPetitionProperty = CustomPetitionProperty::factory()->create([
            'type' => CustomPetitionPropertyType::OPTION,
            'petition_type_id' => $this->petition->petition_type_id,
        ]);
        $this->petition->customPetitionProperties()->sync([$customPetitionProperty->id->toString()]);

        $this->beUser($this->writeUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_EDIT, $this->createRouteParams())
            ->assertOk()
            ->assertViewIs('form');
    }

    public function testEditCustomPetitionPropertiesNotFound(): void
    {
        $this->beUser($this->writeUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_EDIT, $this->createRouteParams([
                'petition' => $this->faker->uuid(),
            ]))
            ->assertNotFound();
    }

    public function testViewCustomPetitionProperties(): void
    {
        $this->beUser($this->readUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_SHOW, $this->createRouteParams())
            ->assertOk();
    }

    public function testViewCustomPetitionPropertiesWithoutPermission(): void
    {
        $this->beUser($this->noPermissionUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_SHOW, $this->createRouteParams())
            ->assertForbidden();
    }

    public function testUpdateCustomPetitionProperties(): void
    {
        $customPetitionProperty = CustomPetitionProperty::factory()
            ->create([
                'type' => CustomPetitionPropertyType::OPTION,
            ]);

        $this->beUser($this->writeUser, true, $this->department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_UPDATE, $this->createRouteParams(), [
                'custom_petition_properties' => [
                    $customPetitionProperty->id->toString(),
                ],
            ])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, $this->createRouteParams());

        $this->petition->refresh();
        $this->assertEquals($customPetitionProperty->id, $this->petition->customPetitionProperties->first()->id);
    }

    public function testUpdateCustomPetitionPropertiesWithoutPermission(): void
    {
        $customPetitionProperty = CustomPetitionProperty::factory()
            ->create([
                'type' => CustomPetitionPropertyType::OPTION,
            ]);

        $this->beUser($this->noPermissionUser)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_UPDATE, $this->createRouteParams(), [
                'custom_petition_properties' => [
                    $customPetitionProperty->id->toString(),
                ],
            ])
            ->assertForbidden();
    }

    public function testUpdateCustomPetitionPropertiesAllowSetToNone(): void
    {
        $this->beUser($this->writeUser, true, $this->department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_UPDATE, $this->createRouteParams())
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, $this->createRouteParams());

        $this->petition->refresh();
        $this->assertEmpty($this->petition->customPetitionProperties);
    }

    public function testUpdateCustomPetitionPropertiesWithHtmx(): void
    {
        $customPetitionProperty = CustomPetitionProperty::factory()
            ->create([
                'type' => CustomPetitionPropertyType::OPTION,
            ]);

        $this->beUser($this->writeUser, true, $this->department)
            ->postByRouteAsHtmx(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_UPDATE, $this->createRouteParams(), [
                'custom_petition_properties' => [
                    $customPetitionProperty->id->toString(),
                ],
            ])
            ->assertOk();

        $this->petition->refresh();
        $this->assertEquals($customPetitionProperty->id, $this->petition->customPetitionProperties->first()->id);
    }

    public function testUpdateCustomPetitionPropertiesRejectsMultipleFromSameGrouping(): void
    {
        $customPetitionProperty1 = $this->createCustomPetitionPropertyWithGrouping(1);
        $customPetitionProperty2 = $this->createCustomPetitionPropertyWithGrouping(1);

        $response = $this->beUser($this->writeUser, true, $this->department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_UPDATE, $this->createRouteParams(), [
                'custom_petition_properties' => [
                    $customPetitionProperty1->id->toString(),
                    $customPetitionProperty2->id->toString(),
                ],
            ]);

        $this->assertValidationErrors($response, [
            $customPetitionProperty1->id->toString(),
            $customPetitionProperty2->id->toString(),
        ]);
    }

    public function testUpdateCustomPetitionPropertiesRejectsMultipleFromSameGroupingWithHtmx(): void
    {
        $customPetitionProperty1 = $this->createCustomPetitionPropertyWithGrouping(1);
        $customPetitionProperty2 = $this->createCustomPetitionPropertyWithGrouping(1);

        $response = $this->beUser($this->writeUser, true, $this->department)
            ->postByRouteAsHtmx(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_UPDATE, $this->createRouteParams(), [
                'custom_petition_properties' => [
                    $customPetitionProperty1->id->toString(),
                    $customPetitionProperty2->id->toString(),
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            $customPetitionProperty1->id->toString(),
            $customPetitionProperty2->id->toString(),
        ]);
    }

    public function testUpdateCustomPetitionPropertiesAllowsMultipleFromDifferentGroupings(): void
    {
        $customPetitionProperty1 = $this->createCustomPetitionPropertyWithGrouping(1);
        $customPetitionProperty2 = $this->createCustomPetitionPropertyWithGrouping(2);

        $this->beUser($this->writeUser, true, $this->department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_UPDATE, $this->createRouteParams(), [
                'custom_petition_properties' => [
                    $customPetitionProperty1->id->toString(),
                    $customPetitionProperty2->id->toString(),
                ],
            ])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, $this->createRouteParams());

        $this->petition->refresh();
        $this->assertCount(2, $this->petition->customPetitionProperties);
    }

    public function testUpdateCustomPetitionPropertiesAllowsMultipleFromIfGroupingIsNull(): void
    {
        $customPetitionProperty1 = $this->createCustomPetitionPropertyWithGrouping(null);
        $customPetitionProperty2 = $this->createCustomPetitionPropertyWithGrouping(null);

        $this->beUser($this->writeUser, true, $this->department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_UPDATE, $this->createRouteParams(), [
                'custom_petition_properties' => [
                    $customPetitionProperty1->id->toString(),
                    $customPetitionProperty2->id->toString(),
                ],
            ])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, $this->createRouteParams());

        $this->petition->refresh();
        $this->assertCount(2, $this->petition->customPetitionProperties);
    }
}
