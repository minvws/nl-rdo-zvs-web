<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Contact;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Contact;
use App\Models\Department;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class ContactArchiveControllerTest extends FeatureTestCase
{
    #[Test]
    public function testArchiveContact(): void
    {
        $department = Department::factory()->create();
        $contact = Contact::factory()->recycle($department)->create([
            'archived_at' => null,
        ]);

        $this->assertNull($contact->archived_at);

        $user = User::factory()->withPermissions(Permission::CONTACT_WRITE)->fullyVerified()->create();
        $this->beUser($user)
            ->postByRoute(RouteName::DEPARTMENTS_CONTACTS_ARCHIVE_STORE, [
                'department' => $department,
                'contact' => $contact,
            ])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_CONTACTS_SHOW, [
                'department' => $department,
                'contact' => $contact,
            ]);

        $contact->refresh();
        $this->assertNotNull($contact->archived_at);
    }

    #[Test]
    public function testArchiveStoreRequiresWritePermission(): void
    {
        $department = Department::factory()->create();
        $contact = Contact::factory()->recycle($department)->create();

        $user = User::factory()->withPermissions(Permission::CONTACT_READ)->fullyVerified()->create();
        $this->beUser($user)
            ->postByRoute(RouteName::DEPARTMENTS_CONTACTS_ARCHIVE_STORE, [
                'department' => $department,
                'contact' => $contact,
            ])
            ->assertForbidden();
    }
}
