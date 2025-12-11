<?php

declare(strict_types=1);

namespace Tests\Smoke\Petition;

use App\Enums\Authorization\DepartmentRole;
use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Contact;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\Petition;
use App\Models\User;
use Tests\Smoke\SmokeTestCase;

use function __;

class PetitionAttachContactsTest extends SmokeTestCase
{
    public function testAttachContactAsApplicantAndRepresentative(): void
    {
        $user = User::factory()
            ->fullyVerified()
            ->withPermissions(Permission::PETITION_WRITE, Permission::CONTACT_MANAGE)
            ->create();
        $department = Department::factory()->create();

        $applicant = Contact::factory()->recycle($department)->create();
        $representative = Contact::factory()->recycle($department)->create();

        $petition = Petition::factory()
            ->recycle($department)
            ->create([
                'applicant_id' => null,
                'representative_id' => null,
            ]);

        DepartmentUser::factory()->create([
            'department_id' => $department->id,
            'user_id' => $user->id,
            'role' => DepartmentRole::WRITE,
        ]);

        $this->beUser($user)
            ->visitRoute(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH_FORM, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertResponseStatus(200)
            ->see($petition->name)
            ->press(__('contact.attached_applicant')) // note that a random contact is selected
            ->assertResponseStatus(200)
            ->press(__('contact.attached_representative')) // note that a random contact is selected!
            ->see(__('general.saved'))
            ->visitRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition->id,
            ])
            ->assertResponseStatus(200)
            ->see(__('contact.attached_applicant'))
            ->see(__('contact.attached_representative'))
            // both contacts should be visible, order can be different since they are randomly picked
            ->see($applicant->last_name)
            ->see($representative->last_name);
    }
}
