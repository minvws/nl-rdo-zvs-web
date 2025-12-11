<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Contact;

use App\Actions\Contact\ContactArchiveAction;
use App\Models\Contact;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class ContactArchiveActionTest extends FeatureTestCase
{
    #[Test]
    public function testArchiveContact(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();
        $contact = Contact::factory()->recycle($department)->create([
            'archived_at' => null,
        ]);

        $this->assertNull($contact->archived_at);

        $action = $this->app->make(ContactArchiveAction::class);

        Carbon::setTestNow('2025-07-10 12:00:00');

        $action->execute($contact, $user);

        $contact->refresh();

        $this->assertNotNull($contact->archived_at);
        $this->assertEquals('2025-07-10 12:00:00', $contact->archived_at->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function testArchiveAlreadyArchivedContactDoesNothing(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();
        $contact = Contact::factory()->recycle($department)->create([
            'archived_at' => Carbon::now()->subDay(),
        ]);

        $originalArchivedAt = $contact->archived_at;

        $action = $this->app->make(ContactArchiveAction::class);

        Carbon::setTestNow('2025-07-10 12:00:00');

        $action->execute($contact, $user);

        $contact->refresh();

        $this->assertNotEquals('2025-07-10 12:00:00', $contact->archived_at->format('Y-m-d H:i:s'));
        $this->assertEquals($originalArchivedAt->format('Y-m-d H:i:s'), $contact->archived_at->format('Y-m-d H:i:s'));
    }
}
