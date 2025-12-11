<?php

declare(strict_types=1);

namespace Tests\Smoke\Petition;

use App\Enums\Authorization\DepartmentRole;
use App\Enums\OptionalFormFieldSetting;
use App\Enums\PetitionTypeType;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use App\Models\User;
use Tests\Helpers\ConfigHelper;
use Tests\Smoke\SmokeTestCase;

use function __;
use function sprintf;
use function strtolower;

class PetitionCreateTest extends SmokeTestCase
{
    public function testCreatePetitionActingAs(): void
    {
        $user = User::factory()->asAdministrator()->fullyVerified()->create();
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()
            ->recycle($department)
            ->create([
                'type' => $this->faker->randomElement([
                    PetitionTypeType::BEROEP,
                    PetitionTypeType::WOO_VERZOEK,
                ]),
            ]);
        PetitionStatus::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create();
        $name = $this->faker->sentence();

        DepartmentUser::factory()->create([
            'department_id' => $department->id,
            'user_id' => $user->id,
            'role' => DepartmentRole::WRITE,
        ]);

        ConfigHelper::set(sprintf('petition_type_type.%s.optional_form_fields', $petitionType->type->value), [
            'name' => OptionalFormFieldSetting::REQUIRED,
            'description' => OptionalFormFieldSetting::REQUIRED,
            'date_appealed_decision' => OptionalFormFieldSetting::EXCLUDED,
            'petition_category_id' => OptionalFormFieldSetting::EXCLUDED,
        ]);

        $this->beUser($user)
            ->visitRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $department])
            ->see(sprintf('<h1>%s</h1>', __('petition.model_plural')))
            ->click('#petitions-create-' . strtolower($petitionType->type->value))
            ->see(sprintf('<h1>%s</h1>', __('petition.create')))
            ->type($name, 'name')
            ->type('omschrijving', 'description')
            ->type('2000-01-01', 'date_of_entry')
            ->press(__('general.create'))
            ->assertResponseStatus(200)
            ->see(sprintf('<span class="petition-details__title">%s</span>', $name))
            ->see('ZK-%s-00001', $department->slug);
    }
}
