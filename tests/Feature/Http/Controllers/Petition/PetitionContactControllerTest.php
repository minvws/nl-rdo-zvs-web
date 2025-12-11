<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\ContactRole;
use App\Enums\CorrespondencePreference;
use App\Enums\RouteName;
use App\Enums\TimelineType;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function __;

class PetitionContactControllerTest extends FeatureTestCase
{
    #[Test]
    public function itPreventAccessToAttachContactsFormWithoutPermission(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->for($department)->create();
        $user = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()->create();

        $response = $this->beUser($user, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH_FORM->value, [
                'department' => $department,
                'petition' => $petition,
            ]);

        $response->assertForbidden();
    }

    #[Test]
    public function itAllowAccessToAttachContactsFormWithPermission(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->for($department)->create();
        $user = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE, Permission::CONTACT_MANAGE)
            ->fullyVerified()->create();

        $response = $this->beUser($user, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH_FORM->value, [
                'department' => $department,
                'petition' => $petition,
            ]);

        $response->assertSuccessful();
    }

    #[Test]
    public function testShowAttachForm(): void
    {
        $departmentA = Department::factory()->create();
        $departmentB = Department::factory()->create();

        $petition = Petition::factory()->recycle($departmentA)->create();

        $contactA = Contact::factory()->recycle($departmentA)->create(['last_name' => 'Contact in Department A']);

        $contactB = Contact::factory()->recycle($departmentB)->create(['last_name' => 'Contact in Department B']);

        $authUser = User::factory()
            ->withPermissionsAndDepartment($departmentA, Permission::PETITION_WRITE, Permission::CONTACT_MANAGE)
            ->fullyVerified()
            ->create();
        $this->beUser($authUser, true, $departmentA)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH_FORM, [
                'department' => $departmentA->slug,
                'petition' => $petition,
            ])
            ->assertOk()
            ->assertSee($contactA->last_name)
            ->assertDontSee($contactB->last_name)
            ->assertViewIs('petition.contacts.attach_contacts');
    }

    #[Test]
    public function testShowAttachFormWithInvalidPetitionId(): void
    {
        $department = Department::factory()->create();
        $authUser = User::factory()
            ->withPermissions(Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH_FORM, [
                'department' => $department,
                'petition' => $this->faker->uuid(),
            ])
            ->assertNotFound();
    }

    #[Test]
    public function testAttachAsRepresentative(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $contact = Contact::factory()->recycle($department)->create();

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();
        $this->beUser($authUser)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH,
                [
                    'department' => $department,
                    'petition' => $petition,
                    'contact' => $contact,
                ],
                [
                    'role' => ContactRole::REPRESENTATIVE->value,
                ],
            )
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH_FORM, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertSessionHas('message.success', __('general.saved'));

        $petition->refresh()->load('contacts');
        $this->assertEquals($contact->id, $petition->representative->first()->id);
    }

    #[Test]
    public function testAttachAsApplicant(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $contact = Contact::factory()->recycle($department)->create();

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();
        $this->beUser($authUser)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH,
                [
                    'department' => $department,
                    'petition' => $petition,
                    'contact' => $contact,
                ],
                [
                    'role' => ContactRole::APPLICANT->value,
                ],
            )
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH_FORM, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertSessionHas('message.success', __('general.saved'));

        $petition->refresh()->load('contacts');
        $this->assertEquals($contact->id, $petition->applicant->first()->id);
    }

    #[Test]
    public function testAttachAsInstitution(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $contact = Contact::factory()->recycle($department)->create();

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();
        $this->beUser($authUser)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH,
                [
                    'department' => $department,
                    'petition' => $petition,
                    'contact' => $contact,
                ],
                [
                    'role' => ContactRole::INSTITUTION->value,
                ],
            )
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH_FORM, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertSessionHas('message.success', __('general.saved'));

        $petition->refresh()->load('contacts');
        $this->assertEquals($contact->id, $petition->institution->first()->id);
    }

    #[Test]
    #[DataProvider('detachContactProvider')]
    public function testDetachContact(ContactRole $role): void
    {
        $department = Department::factory()->create();
        $contact = Contact::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->create();
        $petition->contacts()->attach($contact, ['role' => $role->value]);

        $authUser = User::factory()
            ->withPermissions(Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();
        $this->beUser($authUser)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_CONTACTS_DETACH,
                [
                    'department' => $department,
                    'petition' => $petition,
                    'contact' => $contact,
                ],
                [
                    'role' => $role->value,
                ],
            )
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH_FORM, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertSessionHas('message.success', __('general.saved'));

        $petition->refresh()->load('contacts');
        $this->assertTrue($petition->contacts()->wherePivot('role', $role->value)->doesntExist());
        $this->assertDatabaseEmpty('contact_petition');
    }

    #[Test]
    #[DataProvider('detachContactMismatchProvider')]
    public function testDetachContactWithWrongCombination(string $roleInPivot, ContactRole $role): void
    {
        $department = Department::factory()->create();
        $contact = Contact::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->create();
        $petition->contacts()->attach($contact, ['role' => $roleInPivot]);

        $authUser = User::factory()
            ->withPermissions(Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();
        $this->beUser($authUser)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_CONTACTS_DETACH,
                [
                    'department' => $department,
                    'petition' => $petition,
                    'contact' => $contact,
                ],
                [
                    'role' => $role->value,
                ],
            )
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH_FORM, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertSessionHas('message.success', __('general.saved'));

        $petition->refresh()->load('contacts');
        $this->assertTrue($petition->contacts->first()->pivot->role->value === $roleInPivot);
    }

    #[Test]
    public function testUpdateContactPivot(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $contact = Contact::factory()->recycle($department)->create();

        // Attach contact first
        $petition->contacts()->attach($contact, [
            'role' => ContactRole::APPLICANT->value,
            'reference' => 'old-reference',
            'correspondence_preference' => CorrespondencePreference::EMAIL->value,
        ]);

        $authUser = User::factory()
            ->withPermissions(Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();
        $this->beUser($authUser)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_CONTACTS_UPDATE_PIVOT,
                [
                    'department' => $department,
                    'petition' => $petition,
                    'contact' => $contact,
                ],
                [
                    'reference' => 'new-reference',
                    'correspondence_preference' => CorrespondencePreference::POST->value,
                ],
            )
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH_FORM, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertSessionHas('message.success', __('general.saved'));

        // Verify pivot data was updated
        $petition->refresh();
        $pivotData = $petition->contacts()->where('contact_id', $contact->id)->first()->pivot;
        $this->assertEquals('new-reference', $pivotData->reference);
        $this->assertEquals(CorrespondencePreference::POST, $pivotData->correspondence_preference);

        // Verify timeline entry was created
        $timelineItem = $petition->timelineItems()->latest()->first();
        $this->assertEquals(TimelineType::CONTACT_PIVOT_UPDATED, $timelineItem->type);
    }

    public static function detachContactProvider(): array
    {
        return [
            [ContactRole::APPLICANT],
            [ContactRole::REPRESENTATIVE],
            [ContactRole::INSTITUTION],
        ];
    }

    public static function detachContactMismatchProvider(): array
    {
        return [
            ['applicant', ContactRole::REPRESENTATIVE],
            ['applicant', ContactRole::INSTITUTION],
            ['representative', ContactRole::APPLICANT],
            ['representative', ContactRole::INSTITUTION],
            ['institution', ContactRole::APPLICANT],
            ['institution', ContactRole::REPRESENTATIVE],
        ];
    }

    #[Test]
    public function testPetitionContactAttachmentCrossDepartmentVulnerability(): void
    {
        $departmentA = Department::factory()->create();
        $departmentB = Department::factory()->create();

        $contactFromDepartmentA = Contact::factory()
            ->recycle($departmentA)
            ->create(['last_name' => 'Secret Contact A']);

        $petitionFromDepartmentB = Petition::factory()
            ->recycle($departmentB)
            ->create();

        $userFromDepartmentB = User::factory()
            ->withPermissionsAndDepartment($departmentB, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $response = $this->beUser($userFromDepartmentB, true, $departmentB)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH, [
                'department' => $departmentB->slug,
                'petition' => $petitionFromDepartmentB->id,
                'contact' => $contactFromDepartmentA->id,
            ], [
                'role' => 'applicant', // Add required role field
            ]);

        $response->assertNotFound();
    }
}
