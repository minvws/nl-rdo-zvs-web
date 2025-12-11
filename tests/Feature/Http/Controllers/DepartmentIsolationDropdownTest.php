<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\Authorization\Permission;
use App\Enums\OptionalFormFieldSetting;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionCategory;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use App\Models\PolicyDepartment;
use App\Models\User;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\ConfigHelper;

use function sprintf;
use function str_contains;
use function uniqid;

class DepartmentIsolationDropdownTest extends FeatureTestCase
{
    public function testPetitionCreateFormShowsOnlyDepartmentCategories(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $petitionType1 = PetitionType::factory()->recycle($department1)->create();

        PetitionCategory::factory()->recycle($department1)->create([
            'name' => 'Department 1 Category',
            'active' => true,
        ]);
        PetitionCategory::factory()->recycle($department2)->create([
            'name' => 'Department 2 Category',
            'active' => true,
        ]);

        ConfigHelper::set(
            sprintf('petition_type_type.%s.optional_form_fields.petition_category_id', $petitionType1->type->value),
            OptionalFormFieldSetting::REQUIRED,
        );

        $authUser = User::factory()->withPermissionsAndDepartment($department1, Permission::PETITION_WRITE)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $department1)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CREATE, [
                'department' => $department1->slug,
                'petitionType' => $petitionType1,
            ]);

        $response->assertOk()
            ->assertSee('Department 1 Category')
            ->assertDontSee('Department 2 Category');
    }

    public function testPetitionEditFormShowsOnlyDepartmentCategories(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $petition = Petition::factory()->recycle($department1)->create();

        PetitionCategory::factory()->recycle($department1)->create([
            'name' => 'Department 1 Category',
            'active' => true,
        ]);
        PetitionCategory::factory()->recycle($department2)->create([
            'name' => 'Department 2 Category',
            'active' => true,
        ]);

        ConfigHelper::set(
            sprintf('petition_type_type.%s.optional_form_fields.petition_category_id', $petition->petitionType->type->value),
            OptionalFormFieldSetting::REQUIRED,
        );

        $authUser = User::factory()->withPermissionsAndDepartment($department1, Permission::PETITION_WRITE)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $department1)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_EDIT, [
                'department' => $department1->slug,
                'petition' => $petition,
            ]);

        $response->assertOk()
            ->assertSee('Department 1 Category')
            ->assertDontSee('Department 2 Category');
    }

    public function testPetitionIndexFilterOptionsShowOnlyUsedDepartmentData(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $usedCategory = PetitionCategory::factory()->recycle($department1)->create([
            'name' => 'Department 1 Category',
            'active' => true,
        ]);
        $notUsedCategory = PetitionCategory::factory()->recycle($department1)->create([
            'name' => 'Department 2 Category',
            'active' => true,
        ]);
        PetitionCategory::factory()->recycle($department2)->create([
            'name' => 'Department 2 Category',
            'active' => true,
        ]);

        $petitionType1 = PetitionType::factory()
            ->recycle($department1)
            ->create([
                'name' => 'Department 1 Type',
            ]);
        $petitionType2 = PetitionType::factory()->recycle($department2)->create([
            'name' => 'Department 2 Type',
        ]);

        Petition::factory()->recycle($department1)
            ->recycle($petitionType1)
            ->recycle($usedCategory)
            ->create();
        Petition::factory()->recycle($department2)->recycle($petitionType2)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department1, Permission::PETITION_READ)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $department1)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, [
                'department' => $department1->slug,
            ]);

        $response->assertOk()
            ->assertSee($usedCategory->name)
            ->assertSee($petitionType1->name)
            ->assertDontSee($notUsedCategory->name)
            ->assertDontSee($petitionType2->name);
    }

    public function testPetitionExportIndexFilterOptionsRespectDepartmentIsolation(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $category1 = PetitionCategory::factory()->recycle($department1)->create([
            'name' => 'Export Dept1 Category',
            'active' => true,
        ]);
        $category2 = PetitionCategory::factory()->recycle($department2)->create([
            'name' => 'Export Dept2 Category',
            'active' => true,
        ]);

        $petitionType1 = PetitionType::factory()->recycle($department1)->create([
            'name' => 'Export Dept1 Type',
        ]);
        $petitionType2 = PetitionType::factory()->recycle($department2)->create([
            'name' => 'Export Dept2 Type',
        ]);

        Petition::factory()->recycle($department1)->recycle($petitionType1)->recycle($category1)->create();
        Petition::factory()->recycle($department2)->recycle($petitionType2)->recycle($category2)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department1, Permission::PETITION_READ)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $department1)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_EXPORTS_INDEX, [
                'department' => $department1->slug,
            ]);

        $response->assertOk()
            ->assertSee('Export Dept1 Category')
            ->assertDontSee('Export Dept2 Category');

        if (str_contains($response->getContent(), 'Export Dept2 Type')) {
            $this->fail('PetitionType from department 2 should not be visible in department 1 export options');
        }
    }

    public function testPetitionExportIndexPetitionTypesRespectDepartmentIsolationDept1(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $petitionType1 = PetitionType::factory()->recycle($department1)->create([
            'name' => 'Unique-Export-Dept1-Type-' . uniqid(),
        ]);
        $petitionType2 = PetitionType::factory()->recycle($department2)->create([
            'name' => 'Unique-Export-Dept2-Type-' . uniqid(),
        ]);

        Petition::factory()->recycle($department1)->recycle($petitionType1)->create();
        Petition::factory()->recycle($department2)->recycle($petitionType2)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department1, Permission::PETITION_READ)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $department1)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_EXPORTS_INDEX, [
                'department' => $department1->slug,
            ]);

        $response->assertOk()
            ->assertSee('Unique-Export-Dept1-Type-')
            ->assertDontSee('Unique-Export-Dept2-Type-');
    }

    public function testPetitionExportIndexPetitionTypesRespectDepartmentIsolationDept2(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $petitionType1 = PetitionType::factory()->recycle($department1)->create([
            'name' => 'Unique-Export-Dept1-Type-' . uniqid(),
        ]);
        $petitionType2 = PetitionType::factory()->recycle($department2)->create([
            'name' => 'Unique-Export-Dept2-Type-' . uniqid(),
        ]);

        Petition::factory()->recycle($department1)->recycle($petitionType1)->create();
        Petition::factory()->recycle($department2)->recycle($petitionType2)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department2, Permission::PETITION_READ)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $department2)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_EXPORTS_INDEX, [
                'department' => $department2->slug,
            ]);

        $response->assertOk()
            ->assertSee('Unique-Export-Dept2-Type-')
            ->assertDontSee('Unique-Export-Dept1-Type-');
    }

    public function testPetitionIndexFilterOptionsShowOnlyUsedRelations(): void
    {
        $department = Department::factory()->create();

        $usedPetitionStatus = PetitionStatus::factory()->recycle($department)->create(['status' => 'Used Status']);
        $unusedPetitionStatus = PetitionStatus::factory()->recycle($department)->create(['status' => 'Unused Status']);

        $usedPetitionCategory = PetitionCategory::factory()->recycle($department)->create(['name' => 'Used Category', 'active' => true]);
        $unusedPetitionCategory = PetitionCategory::factory()->recycle($department)->create(
            ['name' => 'Unused Category', 'active' => true],
        );

        $usedPolicyDepartment = PolicyDepartment::factory()->create(['name' => 'Used Policy Dept']);
        $unusedPolicyDepartment = PolicyDepartment::factory()->create(['name' => 'Unused Policy Dept']);

        $usedUser = User::factory()->create(['name' => 'Used User']);
        $unusedUser = User::factory()->create(['name' => 'Unused User']);

        $usedPetitionType = PetitionType::factory()->recycle($department)->create(['name' => 'Used Petition Type']);
        $unusedPetitionType = PetitionType::factory()->recycle($department)->create(['name' => 'Unused Petition Type']);

        $petition = Petition::factory()
            ->recycle($department)
            ->recycle($usedPetitionType)
            ->recycle($usedPetitionStatus)
            ->recycle($usedPetitionCategory)
            ->create([
                'assigned_to' => $usedUser->id,
            ]);

        $petition->policyDepartments()->sync([$usedPolicyDepartment->id->toString()]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_READ)->fullyVerified()->create();
        $response = $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, [
                'department' => $department,
            ]);

        $response->assertOk()
            ->assertSee($usedPetitionStatus->status)
            ->assertSee($usedPolicyDepartment->name)
            ->assertSee($usedUser->name)
            ->assertSee($usedPetitionCategory->name)
            ->assertSee($usedPetitionType->name)
            ->assertDontSee($unusedPetitionStatus->status)
            ->assertDontSee($unusedPolicyDepartment->name)
            ->assertDontSee($unusedUser->name)
            ->assertDontSee($unusedPetitionCategory->name)
            ->assertDontSee($unusedPetitionType->name);
    }
}
