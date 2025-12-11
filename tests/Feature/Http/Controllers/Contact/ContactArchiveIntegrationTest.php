<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Contact;

use App\Actions\Contact\ContactArchiveAction;
use App\Enums\Authorization\Permission;
use App\Models\Contact;
use App\Models\Department;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function now;

class ContactArchiveIntegrationTest extends FeatureTestCase
{
    #[Test]
    public function testContactIsArchivedAfterArchiveAction(): void
    {
        $department = Department::factory()->create();
        $contact = Contact::factory()->recycle($department)->create([
            'archived_at' => null,
        ]);

        $user = User::factory()->withPermissionsAndDepartment($department, Permission::CONTACT_WRITE)->fullyVerified()->create();
        $this->beUser($user, true, $department);

        $this->assertNull($contact->archived_at);

        $archiveAction = $this->app->make(ContactArchiveAction::class);
        $archiveAction->execute($contact, $user);

        $contact->refresh();

        $this->assertNotNull($contact->archived_at);
    }

    #[Test]
    public function testArchivedContactCannotBeArchivedAgain(): void
    {
        $department = Department::factory()->create();
        $contact = Contact::factory()->recycle($department)->create([
            'archived_at' => now(),
        ]);

        $user = User::factory()->withPermissionsAndDepartment($department, Permission::CONTACT_WRITE)->fullyVerified()->create();
        $this->beUser($user, true, $department);

        $originalArchivedAt = $contact->archived_at;

        $archiveAction = $this->app->make(ContactArchiveAction::class);
        $archiveAction->execute($contact, $user);

        $contact->refresh();

        $this->assertEquals($originalArchivedAt->format('Y-m-d H:i:s'), $contact->archived_at->format('Y-m-d H:i:s'));
    }
}
