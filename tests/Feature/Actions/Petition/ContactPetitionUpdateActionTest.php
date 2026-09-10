<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Petition;

use App\Actions\Petition\ContactPetitionUpdateAction;
use App\Enums\ContactRole;
use App\Enums\CorrespondencePreference;
use App\Enums\TimelineType;
use App\Models\Contact;
use App\Models\Petition;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class ContactPetitionUpdateActionTest extends FeatureTestCase
{
    #[Test]
    public function testExecuteUpdatesContactPivotData(): void
    {
        $petition = Petition::factory()->create();
        $contact = Contact::factory()->recycle($petition->department)->create();
        $user = User::factory()->create();

        // Attach contact first
        $petition->contacts()->attach($contact, [
            'role' => ContactRole::APPLICANT->value,
            'reference' => 'old-reference',
            'correspondence_preference' => CorrespondencePreference::EMAIL->value,
        ]);

        $action = $this->app->make(ContactPetitionUpdateAction::class);

        $action->execute($petition, $contact, $user, [
            'reference' => 'new-reference',
            'correspondence_preference' => CorrespondencePreference::POST->value,
            'date_first_phone_contact' => '2026-04-09',
        ]);

        // Check that pivot data was updated
        $pivotData = $petition->contacts()->where('contact_id', $contact->id)->first()->pivot;
        $this->assertEquals('new-reference', $pivotData->reference);
        $this->assertEquals(CorrespondencePreference::POST, $pivotData->correspondence_preference);
        $this->assertSame('2026-04-09', $pivotData->date_first_phone_contact?->format('Y-m-d'));

        // Check timeline entry was created
        $timelineItem = $petition->timelineItems()->firstWhere('type', TimelineType::CONTACT_PIVOT_UPDATED);
        $this->assertEquals($user->id, $timelineItem->user_id);
        $this->assertEquals($contact->id->toString(), $timelineItem->data->contact_id);
        $this->assertEquals('new-reference', $timelineItem->data->reference);
        $this->assertEquals(CorrespondencePreference::POST->value, $timelineItem->data->correspondence_preference);

        // The first phone contact moment gets its own entry on the activity timeline
        $firstPhoneContact = $petition->timelineItems()->firstWhere('type', TimelineType::CONTACT_FIRST_PHONE_CONTACT);
        $this->assertNotNull($firstPhoneContact);
        $this->assertEquals($user->id, $firstPhoneContact->user_id);
        $this->assertEquals($contact->id->toString(), $firstPhoneContact->data->contact_id);
        $this->assertEquals('2026-04-09', $firstPhoneContact->data->date);
    }

    #[Test]
    public function testExecuteDoesNotRepeatTheFirstPhoneContactEntryWhenTheDateIsUnchanged(): void
    {
        $petition = Petition::factory()->create();
        $contact = Contact::factory()->recycle($petition->department)->create();
        $user = User::factory()->create();

        $petition->contacts()->attach($contact, [
            'role' => ContactRole::APPLICANT->value,
            'date_first_phone_contact' => '2026-04-09',
        ]);

        $action = $this->app->make(ContactPetitionUpdateAction::class);

        $action->execute($petition, $contact, $user, [
            'reference' => 'new-reference',
            'date_first_phone_contact' => '2026-04-09',
        ]);

        $this->assertNull($petition->timelineItems()->firstWhere('type', TimelineType::CONTACT_FIRST_PHONE_CONTACT));
    }

    #[Test]
    public function testExecuteWithNullValues(): void
    {
        $petition = Petition::factory()->create();
        $contact = Contact::factory()->recycle($petition->department)->create();
        $user = User::factory()->create();

        // Attach contact with some initial data
        $petition->contacts()->attach($contact, [
            'role' => ContactRole::REPRESENTATIVE->value,
            'reference' => 'initial-reference',
            'correspondence_preference' => CorrespondencePreference::EMAIL->value,
            'date_first_phone_contact' => '2026-04-09',
        ]);

        $action = $this->app->make(ContactPetitionUpdateAction::class);

        $action->execute($petition, $contact, $user, []);

        // Check that pivot data was cleared
        $pivotData = $petition->contacts()->where('contact_id', $contact->id)->first()->pivot;
        $this->assertNull($pivotData->reference);
        $this->assertNull($pivotData->correspondence_preference);
        $this->assertNull($pivotData->date_first_phone_contact);

        // Check timeline entry was created
        $timelineItem = $petition->timelineItems()->latest()->first();
        $this->assertEquals(TimelineType::CONTACT_PIVOT_UPDATED, $timelineItem->type);
        $this->assertEquals($user->id, $timelineItem->user_id);
        $this->assertEquals($contact->id->toString(), $timelineItem->data->contact_id);
        $this->assertNull($timelineItem->data->reference);
        $this->assertNull($timelineItem->data->correspondence_preference);
        $this->assertNull($petition->timelineItems()->firstWhere('type', TimelineType::CONTACT_FIRST_PHONE_CONTACT));
    }
}
