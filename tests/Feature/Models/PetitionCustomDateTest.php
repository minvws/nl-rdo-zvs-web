<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\CustomDateLabel;
use App\Models\Petition;
use App\Models\PetitionCustomDate;
use App\ValueObjects\CalendarDate;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class PetitionCustomDateTest extends FeatureTestCase
{
    #[Test]
    public function testPetitionRelationship(): void
    {
        $petition = Petition::factory()->create();

        $customDate = $petition->customDates()->create([
            'date_label' => CustomDateLabel::DATE_RULING,
            'date' => CalendarDate::create('2025-01-15'),
        ]);

        $this->assertInstanceOf(PetitionCustomDate::class, $customDate);
        $this->assertEquals($petition->id, $customDate->petition_id);
        $this->assertInstanceOf(Petition::class, $customDate->petition);
        $this->assertEquals($petition->id, $customDate->petition->id);
    }

    #[Test]
    public function testPetitionRelationshipIsLoaded(): void
    {
        $petition = Petition::factory()->create();

        $customDate = PetitionCustomDate::factory()->create([
            'petition_id' => $petition->id,
            'date_label' => CustomDateLabel::DATE_DECISION_ON_APPEAL,
            'date' => CalendarDate::create('2025-02-20'),
        ]);

        // Load the relationship explicitly
        $customDate->load('petition');

        $this->assertTrue($customDate->relationLoaded('petition'));
        $this->assertInstanceOf(Petition::class, $customDate->petition);
        $this->assertEquals($petition->id, $customDate->petition->id);
    }

    #[Test]
    public function testBelongsToRelationshipWithEagerLoading(): void
    {
        $petition = Petition::factory()->create();

        PetitionCustomDate::factory()->create([
            'petition_id' => $petition->id,
            'date_label' => CustomDateLabel::DATE_WITHDRAWN,
            'date' => CalendarDate::create('2025-03-10'),
        ]);

        // Retrieve with eager loading
        $customDate = PetitionCustomDate::with('petition')
            ->where('petition_id', $petition->id)
            ->first();

        $this->assertNotNull($customDate);
        $this->assertTrue($customDate->relationLoaded('petition'));
        $this->assertEquals($petition->id, $customDate->petition->id);
    }

    #[Test]
    public function testModelAttributes(): void
    {
        $petition = Petition::factory()->create();
        $date = CalendarDate::create('2025-04-05');

        $customDate = PetitionCustomDate::factory()->create([
            'petition_id' => $petition->id,
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
            'date' => $date,
        ]);

        $this->assertEquals($petition->id, $customDate->petition_id);
        $this->assertEquals(CustomDateLabel::DATE_PUBLIC_HEARING, $customDate->date_label);
        $this->assertEquals($date->format('Y-m-d'), $customDate->date->format('Y-m-d'));
    }

    #[Test]
    public function testNullDate(): void
    {
        $petition = Petition::factory()->create();

        $customDate = PetitionCustomDate::factory()->create([
            'petition_id' => $petition->id,
            'date_label' => CustomDateLabel::DATE_APPOINTMENT_WITH_APPLICANT,
            'date' => null,
        ]);

        $this->assertNull($customDate->date);
        $this->assertEquals(CustomDateLabel::DATE_APPOINTMENT_WITH_APPLICANT, $customDate->date_label);
        $this->assertInstanceOf(Petition::class, $customDate->petition);
    }
}
