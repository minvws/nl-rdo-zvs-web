<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Contact;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Contact;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function __;
use function route;

class ContactShowArchiveButtonTest extends FeatureTestCase
{
    #[Test]
    public function testArchiveButtonIsShownForNonArchivedContact(): void
    {
        $department = Department::factory()->create();
        $contact = Contact::factory()->recycle($department)->create([
            'archived_at' => null,
        ]);

        $user = User::factory()->withPermissions(Permission::CONTACT_READ, Permission::CONTACT_WRITE)->fullyVerified()->create();

        $this->beUser($user)
            ->getByRoute(RouteName::DEPARTMENTS_CONTACTS_EDIT, [
                'department' => $department,
                'contact' => $contact,
            ])
            ->assertOk()
            ->assertSee(__('contact.archive'));
    }

    #[Test]
    public function testArchiveButtonIsNotShownForArchivedContact(): void
    {
        $department = Department::factory()->create();
        $contact = Contact::factory()->recycle($department)->create([
            'archived_at' => Carbon::now(),
        ]);

        $user = User::factory()->withPermissions(Permission::CONTACT_READ, Permission::CONTACT_WRITE)->fullyVerified()->create();

        $this->beUser($user)
            ->getByRoute(RouteName::DEPARTMENTS_CONTACTS_EDIT, [
                'department' => $department,
                'contact' => $contact,
            ])
            ->assertOk()
            ->assertDontSee(__('contact.archive'));
    }

    #[Test]
    public function testEditButtonUsesCorrectPermission(): void
    {
        $department = Department::factory()->create();
        $contact = Contact::factory()->recycle($department)->create();

        $user = User::factory()->withPermissions(Permission::CONTACT_READ, Permission::CONTACT_WRITE)->fullyVerified()->create();

        $this->beUser($user)
            ->getByRoute(RouteName::DEPARTMENTS_CONTACTS_EDIT, [
                'department' => $department,
                'contact' => $contact,
            ])
            ->assertOk()
            ->assertSee(__('contact.edit'))
            ->assertSee(route(RouteName::DEPARTMENTS_CONTACTS_EDIT, ['department' => $department, 'contact' => $contact]));
    }
}
