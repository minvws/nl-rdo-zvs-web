<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Enums\CustomDateLabel;
use App\Enums\PetitionEventType;
use App\Enums\PetitionTypeType;
use App\Enums\ResultType;
use App\Enums\SuspensionType;
use App\Enums\TermType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionCustomDate;
use App\Models\PetitionEvent;
use App\Models\PetitionTerm;
use App\Models\PetitionType;
use App\ValueObjects\CalendarDate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function sprintf;
use function uniqid;

final class MigratePetitionTermsCommandTest extends FeatureTestCase
{
    #[Test]
    public function testCommandFailsWhenNoZaaknummerProvided(): void
    {
        $this->artisan('petition:migrate-terms', ['zaaknummer' => ''])
            ->expectsOutput('U dient een zaaknummer op te geven')
            ->assertFailed();
    }

    #[Test]
    public function testCommandFailsWhenPetitionNotFound(): void
    {
        $this->artisan('petition:migrate-terms', ['zaaknummer' => 'NIET-BESTAAND'])
            ->expectsOutput('Petitie met zaaknummer "NIET-BESTAAND" niet gevonden')
            ->assertFailed();
    }

    #[Test]
    public function testCommandFailsForBeroepPetitionType(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->create([
            'department_id' => $department->id,
            'type' => PetitionTypeType::BEROEP,
        ]);

        Petition::factory()->create([
            'department_id' => $department->id,
            'petition_type_id' => $petitionType->id,
            'number' => 'BEROEP-123',
        ]);

        $this->artisan('petition:migrate-terms', ['zaaknummer' => 'BEROEP-123'])
            ->expectsOutput('Deze zaak BEROEP-123 is van het type beroep en heeft geen termijnen')
            ->assertFailed();
    }

    #[Test]
    #[DataProvider('blockingTermTypesProvider')]
    public function testCommandFailsWhenBlockingTermTypes(TermType $termType, string $expectedMessage): void
    {
        $caseNumber = sprintf('BEZWAAR-%s', uniqid());
        $petition = $this->createBezwaarPetition($caseNumber);

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => $termType,
            'start_date' => CalendarDate::create('2024-01-01'),
        ]);

        $this->artisan('petition:migrate-terms', ['zaaknummer' => $caseNumber])
            ->expectsOutput($expectedMessage)
            ->assertFailed();
    }

    public static function blockingTermTypesProvider(): array
    {
        return [
            'appeal_not_timely' => [TermType::APPEAL_NOT_TIMELY, 'Petitie heeft beroep niet tijdig'],
            'unspecified_adjournment_until_event' => [TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_EVENT, 'Petitie heeft unspecified_adjournment_until_event'],
            'unspecified_adjournment_until_withdrawal' => [TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_WITHDRAWAL, 'Petitie heeft unspecified_adjournment_until_withdrawal'],
            'pending_term_after_event' => [TermType::PENDING_TERM_AFTER_EVENT, 'Petitie heeft pending_term_after_event'],
            'pending_term_after_withdrawal' => [TermType::PENDING_TERM_AFTER_WITHDRAWAL, 'Petitie heeft pending_term_after_withdrawal'],
            'penalty' => [TermType::PENALTY, 'Petitie heeft penalty'],
        ];
    }

    #[Test]
    public function testCommandFailsWhenPetitionHasExistingEventsWithoutOverwrite(): void
    {
        $petition = $this->createBezwaarPetition('BEZWAAR-004');

        PetitionEvent::factory()->count(3)->create([
            'petition_id' => $petition->id,
        ]);

        $this->artisan('petition:migrate-terms', ['zaaknummer' => 'BEZWAAR-004'])
            ->expectsOutput('Petitie heeft al 3 events. Gebruik --overwrite om deze opnieuw te schrijven.')
            ->assertFailed();
    }

    #[Test]
    public function testCommandSuccessfullyMigratesBezwaarTermsInDryRunMode(): void
    {
        $petition = $this->createBezwaarPetition('BEZWAAR-DRY-' . uniqid());

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::FIRST,
            'start_date' => CalendarDate::create('2024-01-10'),
            'duration_in_days' => 42,
        ]);

        $this->artisan('petition:migrate-terms', ['zaaknummer' => $petition->number])
            ->expectsOutput('Dry-run voltooid. Geen wijzigingen opgeslagen.')
            ->assertSuccessful();

        $this->assertEquals(0, PetitionEvent::query()->where('petition_id', $petition->id)->count());
    }

    #[Test]
    public function testCommandSuccessfullyMigratesBezwaarTermsWithCommit(): void
    {
        $petition = $this->createBezwaarPetition('BEZWAAR-006');

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::FIRST,
            'start_date' => CalendarDate::create('2024-01-10'),
            'duration_in_days' => 42,
        ]);

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::SECOND,
            'start_date' => CalendarDate::create('2024-02-10'),
            'duration_in_days' => 14,
        ]);

        $this->artisan('petition:migrate-terms', ['zaaknummer' => 'BEZWAAR-006', '--commit' => true])
            ->expectsOutput('Petition terms succesvol gemigreerd naar events.')
            ->assertSuccessful();

        $events = PetitionEvent::query()
            ->where('petition_id', $petition->id)
            ->orderBy('date')
            ->get();

        $this->assertEquals(2, $events->count());

        $firstEvent = $events->first();
        $this->assertEquals(PetitionEventType::RECEIPT_OF_OBJECTION, $firstEvent->type);
        $this->assertEquals($petition->date_of_entry->toDateString(), $firstEvent->date->toDateString());
        $this->assertEquals(42, $firstEvent->duration);

        $secondEvent = $events->get(1);
        $this->assertEquals(PetitionEventType::ADJOURNMENT, $secondEvent->type);
        $this->assertEquals('2024-02-09', $secondEvent->date->toDateString());
        $this->assertEquals(14, $secondEvent->duration);
    }

    #[Test]
    public function testCommandMigratesAllBezwaarTermTypes(): void
    {
        $petition = $this->createBezwaarPetition('BEZWAAR-007');

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::FIRST,
            'start_date' => CalendarDate::create('2024-01-10'),
            'duration_in_days' => 42,
        ]);

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::SECOND,
            'start_date' => CalendarDate::create('2024-02-10'),
            'duration_in_days' => 14,
        ]);

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::THIRD,
            'start_date' => CalendarDate::create('2024-03-10'),
            'duration_in_days' => 7,
        ]);

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::OBJECTION_PERIOD,
            'start_date' => CalendarDate::create('2024-01-01'),
            'duration_in_days' => 6,
        ]);

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::COMMITTEE_HEARING,
            'start_date' => CalendarDate::create('2024-04-10'),
            'duration_in_days' => 30,
        ]);

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::SUSPENSION,
            'start_date' => CalendarDate::create('2024-05-10'),
            'duration_in_days' => 60,
        ]);

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::SPECIFIED_ADJOURNMENT,
            'start_date' => CalendarDate::create('2024-06-10'),
            'duration_in_days' => 21,
        ]);

        $this->artisan('petition:migrate-terms', ['zaaknummer' => 'BEZWAAR-007', '--commit' => true])
            ->assertSuccessful();

        $events = PetitionEvent::query()
            ->where('petition_id', $petition->id)
            ->orderBy('date')
            ->get();

        $this->assertEquals(7, $events->count());

        $this->assertEquals(PetitionEventType::PRIMARY_DECISION, $events->get(0)->type);
        $this->assertEquals(PetitionEventType::RECEIPT_OF_OBJECTION, $events->get(1)->type);
        $this->assertEquals(PetitionEventType::ADJOURNMENT, $events->get(2)->type);
        $this->assertEquals(PetitionEventType::MEETING_SCHEDULED, $events->get(3)->type);
        $this->assertEquals(PetitionEventType::MEETING_SCHEDULED, $events->get(4)->type);
        $this->assertEquals(PetitionEventType::LETTER_OF_SUSPENSION_SENT, $events->get(5)->type);
        $this->assertEquals(SuspensionType::SUSPENSION, $events->get(5)->suspension_type);
        $this->assertEquals(PetitionEventType::LETTER_OF_SUSPENSION_SENT, $events->get(6)->type);
        $this->assertEquals(SuspensionType::SPECIFIED_ADJOURNMENT, $events->get(6)->suspension_type);
    }

    #[Test]
    public function testCommandMigratesWooVerzoekTerms(): void
    {
        $petition = $this->createWooVerzoekPetition('WOO-001');

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::FIRST,
            'start_date' => CalendarDate::create('2024-01-10'),
            'duration_in_days' => 28,
        ]);

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::SECOND,
            'start_date' => CalendarDate::create('2024-02-10'),
            'duration_in_days' => 14,
        ]);

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::THIRD,
            'start_date' => CalendarDate::create('2024-03-10'),
            'duration_in_days' => 7,
        ]);

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::SUSPENSION,
            'start_date' => CalendarDate::create('2024-04-10'),
            'duration_in_days' => 42,
        ]);

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::NOTICE_OF_DEFAULT,
            'start_date' => CalendarDate::create('2024-05-10'),
            'duration_in_days' => 14,
        ]);

        $this->artisan('petition:migrate-terms', ['zaaknummer' => 'WOO-001', '--commit' => true])
            ->assertSuccessful();

        $events = PetitionEvent::query()
            ->where('petition_id', $petition->id)
            ->orderBy('date')
            ->get();

        $this->assertEquals(5, $events->count());

        $this->assertEquals(PetitionEventType::PETITION_RECEIVED, $events->get(0)->type);
        $this->assertEquals('2024-01-09', $events->get(0)->date->toDateString());

        $this->assertEquals(PetitionEventType::ADJOURNMENT, $events->get(1)->type);
        $this->assertEquals('2024-02-09', $events->get(1)->date->toDateString());

        $this->assertEquals(PetitionEventType::MEETING_SCHEDULED, $events->get(2)->type);
        $this->assertEquals('2024-03-09', $events->get(2)->date->toDateString());

        $this->assertEquals(PetitionEventType::LETTER_OF_SUSPENSION_SENT, $events->get(3)->type);
        $this->assertEquals(SuspensionType::SPECIFICATION, $events->get(3)->suspension_type);
        $this->assertEquals('2024-04-09', $events->get(3)->date->toDateString());

        $this->assertEquals(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, $events->get(4)->type);
        $this->assertEquals('2024-05-09', $events->get(4)->date->toDateString());
    }

    #[Test]
    public function testCommandMigratesBezwaarCustomDates(): void
    {
        $petition = $this->createBezwaarPetition('BEZWAAR-008');

        PetitionCustomDate::factory()->create([
            'petition_id' => $petition->id,
            'date_label' => CustomDateLabel::DATE_DECISION_ON_APPEAL,
            'date' => CalendarDate::create('2024-06-15'),
        ]);

        PetitionCustomDate::factory()->create([
            'petition_id' => $petition->id,
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
            'date' => CalendarDate::create('2024-05-20'),
        ]);

        PetitionCustomDate::factory()->create([
            'petition_id' => $petition->id,
            'date_label' => CustomDateLabel::DATE_WITHDRAWN,
            'date' => CalendarDate::create('2024-07-01'),
        ]);

        $this->artisan('petition:migrate-terms', ['zaaknummer' => 'BEZWAAR-008', '--commit' => true])
            ->assertSuccessful();

        $events = PetitionEvent::query()
            ->where('petition_id', $petition->id)
            ->orderBy('date')
            ->get();

        $this->assertEquals(3, $events->count());

        $hearingEvent = $events->get(0);
        $this->assertEquals(PetitionEventType::HEARING_DATE, $hearingEvent->type);
        $this->assertEquals('2024-05-20', $hearingEvent->date->toDateString());
        $this->assertNull($hearingEvent->result_type);

        $decisionEvent = $events->get(1);
        $this->assertEquals(PetitionEventType::FINAL_RESULT, $decisionEvent->type);
        $this->assertEquals('2024-06-15', $decisionEvent->date->toDateString());
        $this->assertEquals(ResultType::FINAL_DECISION, $decisionEvent->result_type);

        $withdrawnEvent = $events->get(2);
        $this->assertEquals(PetitionEventType::FINAL_RESULT, $withdrawnEvent->type);
        $this->assertEquals('2024-07-01', $withdrawnEvent->date->toDateString());
        $this->assertEquals(ResultType::WITHDRAWN, $withdrawnEvent->result_type);
    }

    #[Test]
    public function testCommandMigratesWooVerzoekCustomDates(): void
    {
        $petition = $this->createWooVerzoekPetition('WOO-002');

        PetitionCustomDate::factory()->create([
            'petition_id' => $petition->id,
            'date_label' => CustomDateLabel::DATE_OF_LAST_DECISION,
            'date' => CalendarDate::create('2024-06-15'),
        ]);

        PetitionCustomDate::factory()->create([
            'petition_id' => $petition->id,
            'date_label' => CustomDateLabel::DATE_SETTLEMENT_WITHOUT_DECISION,
            'date' => CalendarDate::create('2024-05-20'),
        ]);

        $this->artisan('petition:migrate-terms', ['zaaknummer' => 'WOO-002', '--commit' => true])
            ->expectsOutputToContain('Geen event mapping gevonden voor custom date label "date_settlement_without_decision"')
            ->assertSuccessful();

        $events = PetitionEvent::query()
            ->where('petition_id', $petition->id)
            ->get();

        $this->assertEquals(1, $events->count());

        $event = $events->first();
        $this->assertEquals(PetitionEventType::FINAL_RESULT, $event->type);
        $this->assertEquals('2024-06-15', $event->date->toDateString());
        $this->assertEquals(ResultType::FINAL_DECISION, $event->result_type);
    }

    #[Test]
    public function testCommandSkipsCustomDatesWithNullValues(): void
    {
        $petition = $this->createBezwaarPetition('BEZWAAR-009');

        PetitionCustomDate::factory()->create([
            'petition_id' => $petition->id,
            'date_label' => CustomDateLabel::DATE_DECISION_ON_APPEAL,
            'date' => null,
        ]);

        PetitionCustomDate::factory()->create([
            'petition_id' => $petition->id,
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
            'date' => CalendarDate::create('2024-05-20'),
        ]);

        $this->artisan('petition:migrate-terms', ['zaaknummer' => 'BEZWAAR-009', '--commit' => true])
            ->assertSuccessful();

        $events = PetitionEvent::query()
            ->where('petition_id', $petition->id)
            ->get();

        $this->assertEquals(1, $events->count());
        $this->assertEquals(PetitionEventType::HEARING_DATE, $events->first()->type);
    }

    #[Test]
    public function testCommandDeletesExistingEventsWithOverwriteOption(): void
    {
        $petition = $this->createBezwaarPetition('BEZWAAR-010');

        PetitionEvent::factory()->count(3)->create([
            'petition_id' => $petition->id,
        ]);

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::FIRST,
            'start_date' => CalendarDate::create('2024-01-10'),
            'duration_in_days' => 42,
        ]);

        $this->artisan('petition:migrate-terms', [
            'zaaknummer' => 'BEZWAAR-010',
            '--commit' => true,
            '--overwrite' => true,
        ])
            ->assertSuccessful();

        $events = PetitionEvent::query()
            ->where('petition_id', $petition->id)
            ->get();

        $this->assertEquals(1, $events->count());
        $this->assertEquals(PetitionEventType::RECEIPT_OF_OBJECTION, $events->first()->type);
    }

    #[Test]
    public function testCommandWarnsForUnmappedTermTypes(): void
    {
        $petition = $this->createBezwaarPetition('BEZWAAR-011');

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::DECISION_PERIOD,
            'start_date' => CalendarDate::create('2024-01-10'),
            'duration_in_days' => 30,
        ]);

        $this->artisan('petition:migrate-terms', ['zaaknummer' => 'BEZWAAR-011', '--commit' => true])
            ->expectsOutputToContain('Geen event type gevonden voor term type "decision_period"')
            ->assertSuccessful();

        $events = PetitionEvent::query()
            ->where('petition_id', $petition->id)
            ->get();

        $this->assertEquals(0, $events->count());
    }

    #[Test]
    public function testCommandMigratesTermsAndCustomDatesTogether(): void
    {
        $petition = $this->createBezwaarPetition('BEZWAAR-012');

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::FIRST,
            'start_date' => CalendarDate::create('2024-01-10'),
            'duration_in_days' => 42,
        ]);

        PetitionCustomDate::factory()->create([
            'petition_id' => $petition->id,
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
            'date' => CalendarDate::create('2024-05-20'),
        ]);

        $this->artisan('petition:migrate-terms', ['zaaknummer' => 'BEZWAAR-012', '--commit' => true])
            ->assertSuccessful();

        $events = PetitionEvent::query()
            ->where('petition_id', $petition->id)
            ->orderBy('date')
            ->get();

        $this->assertEquals(2, $events->count());
        $this->assertEquals(PetitionEventType::RECEIPT_OF_OBJECTION, $events->get(0)->type);
        $this->assertEquals(PetitionEventType::HEARING_DATE, $events->get(1)->type);
    }

    private function createBezwaarPetition(string $number): Petition
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->create([
            'department_id' => $department->id,
            'type' => PetitionTypeType::BEZWAAR,
        ]);

        return Petition::factory()->create([
            'department_id' => $department->id,
            'petition_type_id' => $petitionType->id,
            'number' => $number,
            'date_of_entry' => CalendarDate::create('2024-01-05'),
        ]);
    }

    private function createWooVerzoekPetition(string $number): Petition
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->create([
            'department_id' => $department->id,
            'type' => PetitionTypeType::WOO_VERZOEK,
        ]);

        return Petition::factory()->create([
            'department_id' => $department->id,
            'petition_type_id' => $petitionType->id,
            'number' => $number,
            'date_of_entry' => CalendarDate::create('2024-01-05'),
        ]);
    }
}
