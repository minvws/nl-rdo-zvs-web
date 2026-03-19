<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\PetitionEvent;

use App\Actions\PetitionEvent\UpdatePetitionTotalsFromEventsAction;
use App\Enums\PetitionEventType;
use App\Enums\SuspensionType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionEvent;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use App\ValueObjects\CalendarDate;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function app;

final class UpdatePetitionTotalsFromEventsActionTest extends FeatureTestCase
{
    #[Test]
    public function testCalculatesTotalDaysSuspendedFromCalendar(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $petition = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create(['date_of_entry' => CalendarDate::create('2025-01-01')]);

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

        $petition->refresh();

        $action = app(UpdatePetitionTotalsFromEventsAction::class);
        $action->execute($petition);

        $petition->refresh();

        $this->assertGreaterThan(0, $petition->total_days_suspended);
    }

    #[Test]
    public function testCalculatesPenaltyMaximumsFromEventPenalties(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $petition = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create(['date_of_entry' => CalendarDate::create('2025-01-01')]);

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
                'type' => PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                'date' => CalendarDate::create('2025-02-20'),
                'duration' => 28,
                'penalties' => [
                    ['amount' => 100, 'duration' => 14],
                    ['amount' => 75, 'duration' => 14],
                ],
            ]);

        PetitionEvent::factory()
            ->for($petition)
            ->create([
                'type' => PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                'date' => CalendarDate::create('2025-02-20'),
                'duration' => 28,
                'penalties' => [
                    ['amount' => 100, 'duration' => 14],
                    ['amount' => 75, 'duration' => 14],
                ],
            ]);

        $petition->refresh();

        $action = app(UpdatePetitionTotalsFromEventsAction::class);
        $action->execute($petition);

        $petition->refresh();

        $this->assertEquals(4900, $petition->igs_penalty_maximum);
        $this->assertEquals(0, $petition->bnt_penalty_maximum);
    }

    #[Test]
    public function testResetsAllTotalsWhenNoEventsExist(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $petition = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create([
                'date_of_entry' => CalendarDate::create('2025-01-01'),
                'deadline_at' => CalendarDate::create('2025-03-15'),
                'total_days_suspended' => 5,
                'igs_penalty_maximum' => 1000,
                'bnt_penalty_maximum' => 500,
            ]);

        PetitionEvent::factory()
            ->for($petition)
            ->create(['type' => PetitionEventType::RECEIPT_OF_OBJECTION, 'date' => CalendarDate::create('2025-01-05')]);

        $petition->petitionEvents()->delete();
        $petition->refresh();

        $action = app(UpdatePetitionTotalsFromEventsAction::class);
        $action->execute($petition);

        $petition->refresh();

        $this->assertNull($petition->deadline_at);
        $this->assertEquals(0, $petition->total_days_suspended);
        $this->assertEquals(0, $petition->igs_penalty_maximum);
        $this->assertEquals(0, $petition->bnt_penalty_maximum);
        $this->assertEquals(0, $petition->igs_penalty_today);
        $this->assertEquals(0, $petition->bnt_penalty_today);
        $this->assertEquals(0, $petition->igs_forfeited);
        $this->assertEquals(0, $petition->bnt_forfeited);
    }

    #[Test]
    public function testHandlesBothIGSAndBNTEventsCorrectly(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $petition = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create(['date_of_entry' => CalendarDate::create('2025-01-01')]);

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
                'type' => PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                'date' => CalendarDate::create('2025-02-20'),
                'duration' => 28,
                'penalties' => [['amount' => 50, 'duration' => 16]],
            ]);

        PetitionEvent::factory()
            ->for($petition)
            ->create([
                'type' => PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
                'date' => CalendarDate::create('2025-03-20'),
                'duration' => 28,
                'penalties' => [['amount' => 30, 'duration' => 20]],
            ]);

        $petition->refresh();

        $action = app(UpdatePetitionTotalsFromEventsAction::class);
        $action->execute($petition);

        $petition->refresh();

        $this->assertEquals(800, $petition->igs_penalty_maximum);
        $this->assertEquals(600, $petition->bnt_penalty_maximum);
        $this->assertNotNull($petition->deadline_at);
    }

    #[Test]
    public function testPreservesDateOfEntryWhenNoReceiptEvent(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $originalDate = CalendarDate::create('2025-01-01');
        $petition = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create(['date_of_entry' => $originalDate]);

        PetitionEvent::factory()
            ->for($petition)
            ->create([
                'type' => PetitionEventType::PRIMARY_DECISION,
                'date' => CalendarDate::create('2025-01-10'),
                'duration' => 42,
            ]);

        $petition->refresh();

        $action = app(UpdatePetitionTotalsFromEventsAction::class);
        $action->execute($petition);

        $petition->refresh();

        $this->assertTrue($petition->date_of_entry->equals($originalDate));
    }

    #[Test]
    public function testCalculatesPenaltyTodayFromEventPenalties(): void
    {
        Carbon::setTestNow('2025-01-08');

        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $petition = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create(['date_of_entry' => CalendarDate::create('2025-01-01')]);

        PetitionEvent::factory()
            ->for($petition)
            ->create([
                'type' => PetitionEventType::RECEIPT_OF_OBJECTION,
                'date' => CalendarDate::create('2025-01-01'),
                'duration' => 1,
            ]);

        // IGS: budget 01-05 and 01-06 (2 days), penalties 01-07 to 01-11 (5 days at 100€)
        // On 2025-01-08 we are on penalty day 2, so 100€
        PetitionEvent::factory()
            ->for($petition)
            ->create([
                'type' => PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                'date' => CalendarDate::create('2025-01-04'),
                'duration' => 2,
                'penalties' => [
                    ['amount' => 100, 'duration' => 5],
                ],
            ]);

        $petition->refresh();

        $action = app(UpdatePetitionTotalsFromEventsAction::class);
        $action->execute($petition);

        $petition->refresh();

        // On 2025-01-08 we are on penalty day 2 (out of 5), so 100€ today
        $this->assertEquals(100, $petition->igs_penalty_today);
        // Forfeited is cumulative: 2025-01-07 (100€) + 2025-01-08 (100€) = 200€
        $this->assertEquals(200, $petition->igs_forfeited);
        // Maximum is sum of all penalties: 5 days * 100€ = 500€
        $this->assertEquals(500, $petition->igs_penalty_maximum);
    }
}
