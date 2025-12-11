<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\OptionalFormFieldSetting;
use App\Enums\PetitionTypeType;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\ConfigHelper;

use function now;
use function sprintf;

class PetitionCreateControllerTest extends FeatureTestCase
{
    #[Test]
    public function testCreatePetitionWithValidCustomNumber(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionTypeType::WOO_VERZOEK]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create(['order' => 1]);

        ConfigHelper::set(sprintf('petition_type_type.%s.optional_form_fields', $petitionType->type->value), [
            'name' => OptionalFormFieldSetting::EXCLUDED,
            'description' => OptionalFormFieldSetting::EXCLUDED,
            'date_appealed_decision' => OptionalFormFieldSetting::EXCLUDED,
            'petition_category_id' => OptionalFormFieldSetting::EXCLUDED,
        ]);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE, Permission::PETITION_NUMBER_OVERRULE)
            ->fullyVerified()
            ->create();

        $customNumber = 'my-custom-number';

        $response = $this->beUser($user, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_STORE, [
                'department' => $department,
                'petitionType' => $petitionType,
            ], [
                'number' => $customNumber,
                'date_of_entry' => now()->toDateString(),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas(Petition::class, [
            'number' => $customNumber,
            'department_id' => $department->id,
        ]);
    }

    #[Test]
    public function testCreatePetitionWithDuplicateNumberFails(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create(['order' => 1]);

        ConfigHelper::set(sprintf('petition_type_type.%s.optional_form_fields', $petitionType->type->value), [
            'name' => OptionalFormFieldSetting::EXCLUDED,
            'description' => OptionalFormFieldSetting::EXCLUDED,
            'date_appealed_decision' => OptionalFormFieldSetting::EXCLUDED,
            'petition_category_id' => OptionalFormFieldSetting::EXCLUDED,
        ]);

        Petition::factory()->recycle($department)->create([
            'number' => '2025X12345',
        ]);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE, Permission::PETITION_NUMBER_OVERRULE)
            ->fullyVerified()
            ->create();

        $response = $this->beUser($user, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_STORE, [
                'department' => $department,
                'petitionType' => $petitionType,
            ], [
                'number' => '2025X12345',
                'date_of_entry' => now()->toDateString(),
            ]);

        $response->assertSessionHasErrors('number');
    }

    #[Test]
    public function testCreatePetitionWithoutNumberGeneratesAutomatic(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionTypeType::WOO_VERZOEK]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create(['order' => 1]);

        ConfigHelper::set(sprintf('petition_type_type.%s.optional_form_fields', $petitionType->type->value), [
            'name' => OptionalFormFieldSetting::EXCLUDED,
            'description' => OptionalFormFieldSetting::EXCLUDED,
            'date_appealed_decision' => OptionalFormFieldSetting::EXCLUDED,
            'petition_category_id' => OptionalFormFieldSetting::EXCLUDED,
        ]);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE, Permission::PETITION_NUMBER_OVERRULE)
            ->fullyVerified()
            ->create();

        $response = $this->beUser($user, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_STORE, [
                'department' => $department,
                'petitionType' => $petitionType,
            ], [
                'date_of_entry' => now()->toDateString(),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas(Petition::class, [
            'department_id' => $department->id,
        ]);
    }

    #[Test]
    public function testCreatePetitionWithInvalidCustomNumberFormat(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create(['order' => 1]);

        ConfigHelper::set(sprintf('petition_type_type.%s.optional_form_fields', $petitionType->type->value), [
            'name' => OptionalFormFieldSetting::EXCLUDED,
            'description' => OptionalFormFieldSetting::EXCLUDED,
            'date_appealed_decision' => OptionalFormFieldSetting::EXCLUDED,
            'petition_category_id' => OptionalFormFieldSetting::EXCLUDED,
        ]);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE, Permission::PETITION_NUMBER_OVERRULE)
            ->fullyVerified()
            ->create();

        $customNumber = '2025X12345';

        $response = $this->beUser($user, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_STORE, [
                'department' => $department,
                'petitionType' => $petitionType,
            ], [
                'number' => $customNumber,
                'date_of_entry' => now()->toDateString(),
            ]);

        $response->assertSessionHasErrors('number');
    }

    #[Test]
    public function testCreatePetitionWithValidCustomNumberButWithoutPermissionGeneratesNumber(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionTypeType::WOO_VERZOEK]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create(['order' => 1]);

        ConfigHelper::set(sprintf('petition_type_type.%s.optional_form_fields', $petitionType->type->value), [
            'name' => OptionalFormFieldSetting::EXCLUDED,
            'description' => OptionalFormFieldSetting::EXCLUDED,
            'date_appealed_decision' => OptionalFormFieldSetting::EXCLUDED,
            'petition_category_id' => OptionalFormFieldSetting::EXCLUDED,
        ]);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $customNumber = 'my-custom-number';

        $response = $this->beUser($user, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_STORE, [
                'department' => $department,
                'petitionType' => $petitionType,
            ], [
                'number' => $customNumber,
                'date_of_entry' => now()->toDateString(),
            ]);

        $response->assertRedirect();

        $petition = Petition::query()->first();

        $this->assertDatabaseMissing(Petition::class, ['number' => $customNumber]);
        $this->assertMatchesRegularExpression(Config::get('app.petition_number_pattern'), $petition->number);
    }
}
