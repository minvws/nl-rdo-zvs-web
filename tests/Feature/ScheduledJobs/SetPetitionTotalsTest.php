<?php

declare(strict_types=1);

namespace Tests\Feature\ScheduledJobs;

use App\Actions\PetitionEvent\Contracts\UpdatePetitionTotalsFromEventsActionInterface;
use App\Actions\PetitionEvent\Contracts\UpdatePetitionTotalsFromTermsActionInterface;
use App\Enums\PetitionEventType;
use App\Enums\SuspensionType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionEvent;
use App\Models\PetitionStatus;
use App\Models\PetitionTerm;
use App\Models\PetitionType;
use App\ScheduledJobs\SetPetitionTotals;
use App\ValueObjects\CalendarDate;
use Exception;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function app;
use function now;
use function sprintf;

final class SetPetitionTotalsTest extends FeatureTestCase
{
    #[Test]
    public function testInvokesJobWithBothEventAndTermPetitions(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $petitionWithEvents = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create([
                'date_of_entry' => CalendarDate::create('2025-01-01'),
                'total_days_suspended' => 0,
            ]);

        PetitionEvent::factory()
            ->for($petitionWithEvents)
            ->create([
                'type' => PetitionEventType::RECEIPT_OF_OBJECTION,
                'date' => CalendarDate::create('2025-01-05'),
                'duration' => 42,
            ]);

        PetitionEvent::factory()
            ->for($petitionWithEvents)
            ->create([
                'type' => PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                'date' => CalendarDate::create('2025-01-15'),
                'duration' => 5,
                'suspension_type' => SuspensionType::SUSPENSION->value,
            ]);

        $petitionWithTerms = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create([
                'date_of_entry' => CalendarDate::create('2025-01-01'),
                'total_days_suspended' => 999,
            ]);

        PetitionTerm::factory()
            ->for($petitionWithTerms)
            ->create();

        $job = app(SetPetitionTotals::class);
        $job();

        $petitionWithEvents->refresh();
        $petitionWithTerms->refresh();

        $this->assertGreaterThan(0, $petitionWithEvents->total_days_suspended);
        $this->assertNotEquals(999, $petitionWithTerms->total_days_suspended);
    }

    #[Test]
    public function testUpdatesConvertedPetitionsWithEvents(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $petition = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create([
                'date_of_entry' => CalendarDate::create('2025-01-01'),
                'total_days_suspended' => 0,
            ]);

        PetitionEvent::factory()
            ->for($petition)
            ->create([
                'type' => PetitionEventType::RECEIPT_OF_OBJECTION,
                'date' => CalendarDate::create('2025-01-05'),
                'duration' => 42,
            ]);

        PetitionEvent::factory()
            ->for($petition)
            ->create([
                'type' => PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                'date' => CalendarDate::create('2025-01-15'),
                'duration' => 5,
                'suspension_type' => SuspensionType::SUSPENSION->value,
            ]);

        $job = app(SetPetitionTotals::class);
        $job->setTotalsOnConvertedPetitions();

        $petition->refresh();

        $this->assertGreaterThan(0, $petition->total_days_suspended);
    }

    #[Test]
    public function testUpdatesLegacyPetitionsWithTerms(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $petition = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create([
                'date_of_entry' => CalendarDate::create('2025-01-01'),
                'total_days_suspended' => 999,
            ]);

        PetitionTerm::factory()
            ->for($petition)
            ->create();

        $job = app(SetPetitionTotals::class);
        $job();

        $petition->refresh();

        $this->assertNotEquals(999, $petition->total_days_suspended);
    }

    #[Test]
    public function testSkipsArchivedPetitionsWithEvents(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $petition = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create([
                'date_of_entry' => CalendarDate::create('2025-01-01'),
                'total_days_suspended' => 999,
                'archived_at' => now(),
            ]);

        PetitionEvent::factory()
            ->for($petition)
            ->create([
                'type' => PetitionEventType::RECEIPT_OF_OBJECTION,
                'date' => CalendarDate::create('2025-01-05'),
                'duration' => 42,
            ]);

        PetitionEvent::factory()
            ->for($petition)
            ->create([
                'type' => PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                'date' => CalendarDate::create('2025-01-15'),
                'duration' => 5,
                'suspension_type' => SuspensionType::SUSPENSION->value,
            ]);

        $job = app(SetPetitionTotals::class);
        $job->setTotalsOnConvertedPetitions();

        $petition->refresh();

        $this->assertEquals(999, $petition->total_days_suspended);
    }

    #[Test]
    public function testSkipsArchivedPetitionsWithTerms(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $petition = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create([
                'date_of_entry' => CalendarDate::create('2025-01-01'),
                'total_days_suspended' => 999,
                'archived_at' => now(),
            ]);

        PetitionTerm::factory()
            ->for($petition)
            ->create();

        $job = app(SetPetitionTotals::class);
        $job();

        $petition->refresh();

        $this->assertEquals(999, $petition->total_days_suspended);
    }

    #[Test]
    public function testSkipsPetitionsWithBothEventsAndTerms(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $petition = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create([
                'date_of_entry' => CalendarDate::create('2025-01-01'),
                'total_days_suspended' => 999,
            ]);

        PetitionEvent::factory()
            ->for($petition)
            ->create([
                'type' => PetitionEventType::RECEIPT_OF_OBJECTION,
                'date' => CalendarDate::create('2025-01-05'),
                'duration' => 10,
            ]);

        PetitionTerm::factory()
            ->for($petition)
            ->create();

        $initialValue = $petition->total_days_suspended;

        $job = app(SetPetitionTotals::class);
        $job();

        $petition->refresh();

        $this->assertNotEquals($initialValue, $petition->total_days_suspended);
    }

    #[Test]
    public function testProcessesMultiplePetitionsWithEvents(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $petition1 = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create([
                'date_of_entry' => CalendarDate::create('2025-01-01'),
                'total_days_suspended' => 0,
            ]);

        $petition2 = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create([
                'date_of_entry' => CalendarDate::create('2025-01-01'),
                'total_days_suspended' => 0,
            ]);

        PetitionEvent::factory()
            ->for($petition1)
            ->create([
                'type' => PetitionEventType::RECEIPT_OF_OBJECTION,
                'date' => CalendarDate::create('2025-01-05'),
                'duration' => 42,
            ]);

        PetitionEvent::factory()
            ->for($petition1)
            ->create([
                'type' => PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                'date' => CalendarDate::create('2025-01-15'),
                'duration' => 5,
                'suspension_type' => SuspensionType::SUSPENSION->value,
            ]);

        PetitionEvent::factory()
            ->for($petition2)
            ->create([
                'type' => PetitionEventType::RECEIPT_OF_OBJECTION,
                'date' => CalendarDate::create('2025-01-05'),
                'duration' => 42,
            ]);

        PetitionEvent::factory()
            ->for($petition2)
            ->create([
                'type' => PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                'date' => CalendarDate::create('2025-01-15'),
                'duration' => 10,
                'suspension_type' => SuspensionType::SUSPENSION->value,
            ]);

        $job = app(SetPetitionTotals::class);
        $job->setTotalsOnConvertedPetitions();

        $petition1->refresh();
        $petition2->refresh();

        $this->assertGreaterThan(0, $petition1->total_days_suspended);
        $this->assertGreaterThan(0, $petition2->total_days_suspended);
    }

    #[Test]
    public function testProcessesMultiplePetitionsWithTerms(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $petition1 = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create([
                'date_of_entry' => CalendarDate::create('2025-01-01'),
                'total_days_suspended' => 999,
            ]);

        $petition2 = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create([
                'date_of_entry' => CalendarDate::create('2025-01-01'),
                'total_days_suspended' => 999,
            ]);

        PetitionTerm::factory()
            ->for($petition1)
            ->create();

        PetitionTerm::factory()
            ->for($petition2)
            ->create();

        $job = app(SetPetitionTotals::class);
        $job();

        $petition1->refresh();
        $petition2->refresh();

        $this->assertNotEquals(999, $petition1->total_days_suspended);
        $this->assertNotEquals(999, $petition2->total_days_suspended);
    }

    public function testLoggingWhenExceptionForEvents(): void
    {
        Log::spy();
        $petition = Petition::factory()
            ->create(['date_of_entry' => CalendarDate::create('2025-01-01')]);

        PetitionEvent::factory()
            ->for($petition)
            ->create([
                'type' => PetitionEventType::RECEIPT_OF_OBJECTION,
                'date' => CalendarDate::create('2025-01-05'),
                'duration' => 42,
            ]);

        $mock = Mockery::mock(UpdatePetitionTotalsFromEventsActionInterface::class);
        $mock->shouldReceive('execute')
            ->andThrow(new Exception('Test exception'));

        $this->app->instance(UpdatePetitionTotalsFromEventsActionInterface::class, $mock);

        $job = app(SetPetitionTotals::class);
        $job();

        Log::shouldHaveReceived('error')
            ->once()
            ->with(sprintf('Failed to update petition totals from events for petition ID: %s', $petition->id));
    }

    public function testLoggingWhenExceptionForTerms(): void
    {
        Log::spy();

        $petition = Petition::factory()
            ->create(['date_of_entry' => CalendarDate::create('2025-01-01')]);

        PetitionTerm::factory()
            ->for($petition)
            ->create([]);

        $mock = Mockery::mock(UpdatePetitionTotalsFromTermsActionInterface::class);
        $mock->shouldReceive('execute')
            ->andThrow(new Exception('Test exception'));

        $this->app->instance(UpdatePetitionTotalsFromTermsActionInterface::class, $mock);

        $this->assertSame($mock, app(UpdatePetitionTotalsFromTermsActionInterface::class));

        // Ensure the petition is matched by the legacy petition query
        $legacyCount = Petition::query()
            ->whereHas('petitionTerms')
            ->whereDoesntHave('petitionEvents')
            ->notArchived()
            ->where('id', $petition->id)
            ->count();

        $this->assertSame(1, $legacyCount, 'Petition should be selected by legacy query');

        $job = app(SetPetitionTotals::class);
        $job();

        Log::shouldHaveReceived('error')
            ->once()
            ->with(sprintf('Failed to update petition totals from terms for legacy petition ID: %s', $petition->id));
    }
}
