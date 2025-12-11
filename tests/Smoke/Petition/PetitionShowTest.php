<?php

declare(strict_types=1);

namespace Tests\Smoke\Petition;

use App\Enums\Authorization\DepartmentRole;
use App\Enums\OptionalFormFieldSetting;
use App\Enums\RouteName;
use App\Enums\TermType;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\Petition;
use App\Models\User;
use Tests\Helpers\ConfigHelper;
use Tests\Smoke\SmokeTestCase;

use function route;
use function sprintf;

class PetitionShowTest extends SmokeTestCase
{
    public function testShowPetitionWithWritePermissionShowsButtons(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $department = Department::factory()->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        DepartmentUser::factory()->create([
            'department_id' => $department->id,
            'user_id' => $user->id,
            'role' => DepartmentRole::WRITE,
        ]);

        ConfigHelper::set(sprintf('petition_type_type.%s', $petition->petitionType->type->value), [
            'optional_form_fields' => [
                'name' => OptionalFormFieldSetting::EXCLUDED,
                'description' => OptionalFormFieldSetting::EXCLUDED,
                'date_appealed_decision' => OptionalFormFieldSetting::EXCLUDED,
                'petition_category_id' => OptionalFormFieldSetting::EXCLUDED,
            ],
            'petition_terms_enabled' => true,
            'petition_terms' => [
                TermType::FIRST,
            ],
            'petition_deliverables_enabled' => true,
        ]);

        $this->beUser($user)
            ->visitRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition->id,
            ])
            ->see(sprintf('<span class="petition-details__number">%s</span>', $petition->number))
            ->see(route(RouteName::DEPARTMENTS_PETITIONS_TERMS_CREATE, [
                'department' => $department,
                'petition' => $petition,
                'termType' => TermType::FIRST,
            ]))
            ->see(route(RouteName::DEPARTMENTS_PETITIONS_DECISIONS_CREATE, [
                'department' => $department,
                'petition' => $petition,
            ]))
            ->see(route(RouteName::DEPARTMENTS_PETITION_PETITION_ATTACH_FORM, [
                'department' => $department,
                'petition' => $petition->id,
            ]));
    }

    public function testShowPetitionWithoutWritePermissionDoesNotShowTermButtons(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $department = Department::factory()->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        ConfigHelper::set(sprintf('petition_type_type.%s', $petition->petitionType->type->value), [
            'optional_form_fields' => [
                'name' => OptionalFormFieldSetting::EXCLUDED,
                'description' => OptionalFormFieldSetting::EXCLUDED,
                'date_appealed_decision' => OptionalFormFieldSetting::EXCLUDED,
                'petition_category_id' => OptionalFormFieldSetting::EXCLUDED,
            ],
            'petition_terms_enabled' => true,
            'petition_terms' => [
                TermType::FIRST,
            ],
            'petition_deliverables_enabled' => true,
        ]);

        DepartmentUser::factory()->create([
            'department_id' => $department->id,
            'user_id' => $user->id,
            'role' => DepartmentRole::READ,
        ]);

        $this->beUser($user)
            ->visitRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition->id,
            ])
            ->see(sprintf('<span class="petition-details__number">%s</span>', $petition->number))
            ->dontSee(route(RouteName::DEPARTMENTS_PETITIONS_TERMS_CREATE, [
                'department' => $department,
                'petition' => $petition,
                'termType' => TermType::FIRST,
            ]))
            ->dontSee(route(RouteName::DEPARTMENTS_PETITIONS_DECISIONS_CREATE, [
                'department' => $department,
                'petition' => $petition,
            ]))
            ->dontSee(route(RouteName::DEPARTMENTS_PETITION_PETITION_ATTACH_FORM, [
                'department' => $department,
                'petition' => $petition->id,
            ]));
    }
}
