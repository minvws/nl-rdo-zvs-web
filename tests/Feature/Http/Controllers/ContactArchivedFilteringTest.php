<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use Carbon\Carbon;
use Tests\Feature\FeatureTestCase;

use function collect;

class ContactArchivedFilteringTest extends FeatureTestCase
{
    public function testContactIndexOnlyShowsNonArchivedContacts(): void
    {
        $department = Department::factory()->create();

        $nonArchivedContact = Contact::factory()->recycle($department)->create([
            'archived_at' => null,
        ]);

        $archivedContact = Contact::factory()->recycle($department)->create([
            'archived_at' => Carbon::now(),
        ]);

        $user = User::factory()->withPermissions(Permission::CONTACT_READ)->fullyVerified()->create();
        $response = $this->beUser($user)
            ->getByRoute(RouteName::DEPARTMENTS_CONTACTS_INDEX, ['department' => $department]);

        $response->assertOk();
        $response->assertSee($nonArchivedContact->full_name);
        $response->assertDontSee($archivedContact->full_name);
    }

    public function testApiContactIndexShowsAllContacts(): void
    {
        $department = Department::factory()->create();

        $nonArchivedContact = Contact::factory()->recycle($department)->create([
            'archived_at' => null,
        ]);

        $archivedContact = Contact::factory()->recycle($department)->create([
            'archived_at' => Carbon::now(),
        ]);

        $user = User::factory()->withPermissions(Permission::CONTACT_READ)->fullyVerified()->create();
        $response = $this->beUser($user)
            ->get('/api/v1/contacts');

        $response->assertOk();
        $responseData = $response->json('data');

        $contactIds = collect($responseData)->pluck('id');

        $this->assertTrue($contactIds->contains($nonArchivedContact->id));
        $this->assertTrue($contactIds->contains($archivedContact->id));
    }

    public function testPetitionContactAttachFormOnlyShowsNonArchivedContacts(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $nonArchivedContact = Contact::factory()->recycle($department)->create([
            'archived_at' => null,
        ]);

        $archivedContact = Contact::factory()->recycle($department)->create([
            'archived_at' => Carbon::now(),
        ]);

        $user = User::factory()->withPermissions(Permission::PETITION_WRITE, Permission::CONTACT_MANAGE)->fullyVerified()->create();
        $response = $this->beUser($user)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH_FORM, [
                'department' => $department,
                'petition' => $petition->id,
            ]);

        $response->assertOk();
        $response->assertSee($nonArchivedContact->full_name);
        $response->assertDontSee($archivedContact->full_name);
    }
}
