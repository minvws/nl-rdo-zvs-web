<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\ContactType;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Petition;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function fake;

class ContactTest extends FeatureTestCase
{
    #[Test]
    public function testPetitionsRelationship(): void
    {
        $department = Department::factory()->create();
        $contact = Contact::factory()->create([
            'department_id' => $department->id,
            'type' => ContactType::CIVILIAN,
            'last_name' => fake()->lastName(),
        ]);

        $petition = Petition::factory()->hasDepartment($department)->create();

        $contact->petitions()->attach($petition->id->toString(), ['role' => 'applicant']);

        $this->assertEquals(1, $contact->petitions->count());
        $this->assertInstanceOf(Petition::class, $contact->petitions->first());
        $this->assertEquals('applicant', $contact->petitions->first()->pivot->role->value);
    }

    #[Test]
    public function testPetitionsRelationshipWithPivot(): void
    {
        $contact = Contact::factory()->create();
        $petition = Petition::factory()->create();

        // Use the contact object instead of just the ID, like the application does
        $contact->petitions()->attach($petition, ['role' => 'representative']);

        $retrievedPetition = $contact->petitions()->first();
        $this->assertNotNull($retrievedPetition);
        $this->assertEquals('representative', $retrievedPetition->pivot->role->value);
    }

    #[Test]
    public function testContactCanBeArchived(): void
    {
        $department = Department::factory()->create();
        $contact = Contact::factory()
            ->recycle($department)
            ->create([
                'archived_at' => null,
            ]);

        $this->assertNull($contact->archived_at);

        $contact->update(['archived_at' => Carbon::now()]);
        $contact->refresh();

        $this->assertNotNull($contact->archived_at);
        $this->assertInstanceOf(CarbonImmutable::class, $contact->archived_at);
    }

    #[Test]
    public function testContactFactoryCreatesUnArchivedContactByDefault(): void
    {
        $contact = Contact::factory()->create();

        $this->assertNull($contact->archived_at);
    }

    #[Test]
    public function testContactQueryBuilderNotArchivedMethod(): void
    {
        $department = Department::factory()->create();

        Contact::factory()
            ->recycle($department)
            ->count(3)
            ->create([
                'archived_at' => null,
            ]);

        Contact::factory()
            ->recycle($department)
            ->count(2)
            ->create([
                'archived_at' => Carbon::now(),
            ]);

        $nonArchivedContacts = Contact::query()->notArchived()->get();

        $this->assertCount(3, $nonArchivedContacts);

        foreach ($nonArchivedContacts as $contact) {
            $this->assertNull($contact->archived_at);
        }
    }

    #[Test]
    public function testArchivedContactsAreExcludedFromNotArchivedQuery(): void
    {
        $department = Department::factory()->create();

        $nonArchivedContact = Contact::factory()
            ->recycle($department)
            ->create([
                'archived_at' => null,
            ]);

        $archivedContact = Contact::factory()
            ->recycle($department)
            ->create([
                'archived_at' => Carbon::now(),
            ]);

        $notArchivedResults = Contact::query()->notArchived()->pluck('id');

        $this->assertTrue($notArchivedResults->contains($nonArchivedContact->id));
        $this->assertFalse($notArchivedResults->contains($archivedContact->id));
    }

    #[Test]
    public function testArchivedAtCanBeSetToSpecificDate(): void
    {
        $specificDate = Carbon::createFromFormat('Y-m-d H:i:s', '2025-07-10 12:00:00');
        $contact = Contact::factory()->create();

        $contact->update(['archived_at' => $specificDate]);
        $contact->refresh();

        $this->assertEquals('2025-07-10 12:00:00', $contact->archived_at->format('Y-m-d H:i:s'));
    }
}
