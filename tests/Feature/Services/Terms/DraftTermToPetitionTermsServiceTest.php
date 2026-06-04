<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Terms;

use App\Actions\Petition\PetitionTermsUpdateAction;
use App\Enums\TermType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionDraftTerm;
use App\Models\PetitionTerm;
use App\Models\User;
use App\Services\LegalTermDeadlineCalculator;
use App\Services\Terms\DraftTermToPetitionTermsService;
use App\Services\Terms\TermDateCalculator;
use Tests\Feature\FeatureTestCase;

class DraftTermToPetitionTermsServiceTest extends FeatureTestCase
{
    private DraftTermToPetitionTermsService $service;
    private User $user;
    private Department $department;
    private Petition $petition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(DraftTermToPetitionTermsService::class);
        $this->user = User::factory()->create();
        $this->department = Department::factory()->create();
        $this->petition = Petition::factory()->recycle($this->department)->create();
    }

    public function testDoesNothingWhenNoEventDateOrWithdrawalDate(): void
    {
        $draftTerm = PetitionDraftTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'start_date' => $this->faker->calendarDate(),
            'event_date' => null,
            'date_withdrawal' => null,
            'days_after_event' => 30,
            'days_after_date_withdrawal' => 14,
        ]);

        $this->service->convertDraftTermToPetitionTerms($draftTerm, $this->user);

        $this->assertDatabaseMissing('petition_terms', [
            'petition_id' => $this->petition->id,
        ]);
    }

    public function testCreatesTermsForEventDateWhenOnlyEventDateSet(): void
    {
        $startDate = $this->faker->calendarDate();
        $eventDate = $startDate->addDays(10);

        $draftTerm = PetitionDraftTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'start_date' => $startDate,
            'event_date' => $eventDate,
            'date_withdrawal' => null,
            'days_after_event' => 30,
            'days_after_date_withdrawal' => 14,
        ]);

        // Calculate expected second term duration using deadline calculator
        $secondTermStartDate = $eventDate->addDay();
        $secondTermEndDate = $secondTermStartDate->addDays(30 - 1);
        $deadlineCalculator = $this->app->make(LegalTermDeadlineCalculator::class);
        $adjustedSecondTermEndDate = $deadlineCalculator->calculate($secondTermEndDate);
        $expectedSecondTermDuration = $secondTermStartDate->diffInDays($adjustedSecondTermEndDate) + 1;

        $this->service->convertDraftTermToPetitionTerms($draftTerm, $this->user);

        $this->assertDatabaseHas('petition_terms', [
            'petition_id' => $this->petition->id,
            'type' => TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_EVENT->value,
            'start_date' => $startDate->format('Y-m-d'),
            'duration_in_days' => 11,
            'penalty_amount_in_euros' => 0,
            'parent_id' => null,
        ]);

        $this->assertDatabaseHas('petition_terms', [
            'petition_id' => $this->petition->id,
            'type' => TermType::PENDING_TERM_AFTER_EVENT->value,
            'start_date' => $eventDate->addDay()->format('Y-m-d'),
            'duration_in_days' => $expectedSecondTermDuration,
            'penalty_amount_in_euros' => 0,
        ]);

        $adjournmentTerm = PetitionTerm::where('type', TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_EVENT)->first();
        $pendingTerm = PetitionTerm::where('type', TermType::PENDING_TERM_AFTER_EVENT)->first();

        $this->assertNotNull($adjournmentTerm);
        $this->assertNotNull($pendingTerm);
        $this->assertEquals($adjournmentTerm->id, $pendingTerm->parent_id);

        $this->assertDatabaseMissing('petition_draft_terms', [
            'id' => $draftTerm->id,
        ]);
    }

    public function testCreatesTermsForWithdrawalDateWhenOnlyWithdrawalDateSet(): void
    {
        $startDate = $this->faker->calendarDate();
        $withdrawalDate = $startDate->addDays(15);

        $draftTerm = PetitionDraftTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'start_date' => $startDate,
            'event_date' => null,
            'date_withdrawal' => $withdrawalDate,
            'days_after_event' => 30,
            'days_after_date_withdrawal' => 14,
        ]);

        // Calculate expected second term duration using deadline calculator
        $secondTermStartDate = $withdrawalDate->addDay();
        $secondTermEndDate = $secondTermStartDate->addDays(14 - 1);
        $deadlineCalculator = $this->app->make(LegalTermDeadlineCalculator::class);
        $adjustedSecondTermEndDate = $deadlineCalculator->calculate($secondTermEndDate);
        $expectedSecondTermDuration = $secondTermStartDate->diffInDays($adjustedSecondTermEndDate) + 1;

        $this->service->convertDraftTermToPetitionTerms($draftTerm, $this->user);

        $this->assertDatabaseHas('petition_terms', [
            'petition_id' => $this->petition->id,
            'type' => TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_WITHDRAWAL->value,
            'start_date' => $startDate->format('Y-m-d'),
            'duration_in_days' => 16,
            'penalty_amount_in_euros' => 0,
            'parent_id' => null,
        ]);

        $this->assertDatabaseHas('petition_terms', [
            'petition_id' => $this->petition->id,
            'type' => TermType::PENDING_TERM_AFTER_WITHDRAWAL->value,
            'start_date' => $withdrawalDate->addDay()->format('Y-m-d'),
            'duration_in_days' => $expectedSecondTermDuration,
            'penalty_amount_in_euros' => 0,
        ]);

        $adjournmentTerm = PetitionTerm::where('type', TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_WITHDRAWAL)->first();
        $pendingTerm = PetitionTerm::where('type', TermType::PENDING_TERM_AFTER_WITHDRAWAL)->first();

        $this->assertNotNull($adjournmentTerm);
        $this->assertNotNull($pendingTerm);
        $this->assertEquals($adjournmentTerm->id, $pendingTerm->parent_id);
    }

    public function testUsesEventDateWhenEventDateBeforeWithdrawalDate(): void
    {
        $startDate = $this->faker->calendarDate();
        $eventDate = $startDate->addDays(10);
        $withdrawalDate = $startDate->addDays(20);
        $daysAfterEvent = 30;
        $daysAfterWithdrawal = 14;

        $draftTerm = PetitionDraftTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'start_date' => $startDate,
            'event_date' => $eventDate,
            'date_withdrawal' => $withdrawalDate,
            'days_after_event' => $daysAfterEvent,
            'days_after_date_withdrawal' => $daysAfterWithdrawal,
        ]);

        // Calculate expected second term duration using deadline calculator
        $secondTermStartDate = $eventDate->addDay();
        $secondTermEndDate = TermDateCalculator::calculateEndDate($secondTermStartDate, $daysAfterEvent);
        $deadlineCalculator = $this->app->make(LegalTermDeadlineCalculator::class);
        $adjustedSecondTermEndDate = $deadlineCalculator->calculate($secondTermEndDate);
        $expectedSecondTermDuration = TermDateCalculator::calculateDuration($secondTermStartDate, $adjustedSecondTermEndDate);

        $this->service->convertDraftTermToPetitionTerms($draftTerm, $this->user);

        $this->assertDatabaseHas('petition_terms', [
            'petition_id' => $this->petition->id,
            'type' => TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_EVENT->value,
            'start_date' => $startDate->format('Y-m-d'),
            'duration_in_days' => 11,
            'penalty_amount_in_euros' => 0,
        ]);

        $this->assertDatabaseHas('petition_terms', [
            'petition_id' => $this->petition->id,
            'type' => TermType::PENDING_TERM_AFTER_EVENT->value,
            'start_date' => $eventDate->addDay()->format('Y-m-d'),
            'end_date' => $adjustedSecondTermEndDate->format('Y-m-d'),
            'duration_in_days' => $expectedSecondTermDuration,
            'penalty_amount_in_euros' => 0,
        ]);
    }

    public function testUsesWithdrawalDateWhenWithdrawalDateBeforeEventDate(): void
    {
        $startDate = $this->faker->calendarDate();
        $withdrawalDate = $startDate->addDays(10);
        $eventDate = $startDate->addDays(20);
        $daysAfterEvent = 30;
        $daysAfterWithdrawal = 14;

        $draftTerm = PetitionDraftTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'start_date' => $startDate,
            'event_date' => $eventDate,
            'date_withdrawal' => $withdrawalDate,
            'days_after_event' => $daysAfterEvent,
            'days_after_date_withdrawal' => $daysAfterWithdrawal,
        ]);

        $secondTermStartDate = $withdrawalDate->addDay();
        $secondTermEndDate = TermDateCalculator::calculateEndDate($secondTermStartDate, $daysAfterWithdrawal);
        $deadlineCalculator = $this->app->make(LegalTermDeadlineCalculator::class);
        $adjustedSecondTermEndDate = $deadlineCalculator->calculate($secondTermEndDate);
        $expectedSecondTermDuration = TermDateCalculator::calculateDuration($secondTermStartDate, $adjustedSecondTermEndDate);

        $this->service->convertDraftTermToPetitionTerms($draftTerm, $this->user);

        $this->assertDatabaseHas('petition_terms', [
            'petition_id' => $this->petition->id,
            'type' => TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_WITHDRAWAL->value,
            'start_date' => $startDate->format('Y-m-d'),
            'duration_in_days' => 11,
            'penalty_amount_in_euros' => 0,
        ]);

        $this->assertDatabaseHas('petition_terms', [
            'petition_id' => $this->petition->id,
            'type' => TermType::PENDING_TERM_AFTER_WITHDRAWAL->value,
            'start_date' => $withdrawalDate->addDay()->format('Y-m-d'),
            'duration_in_days' => $expectedSecondTermDuration,
            'penalty_amount_in_euros' => 0,
        ]);
    }

    public function testHandlesNullDaysAfterDateWithdrawal(): void
    {
        $startDate = $this->faker->calendarDate();
        $withdrawalDate = $startDate->addDays(10);

        $draftTerm = PetitionDraftTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'start_date' => $startDate,
            'event_date' => null,
            'date_withdrawal' => $withdrawalDate,
            'days_after_event' => 30,
            'days_after_date_withdrawal' => null,
        ]);

        $baseDuration = TermDateCalculator::calculateDuration($startDate, $withdrawalDate);
        $endDate = TermDateCalculator::calculateEndDate($startDate, $baseDuration);
        $deadlineCalculator = $this->app->make(LegalTermDeadlineCalculator::class);
        $adjustedEndDate = $deadlineCalculator->calculate($endDate);
        $expectedDuration = TermDateCalculator::calculateDuration($startDate, $adjustedEndDate);

        $this->service->convertDraftTermToPetitionTerms($draftTerm, $this->user);

        $this->assertDatabaseHas('petition_terms', [
            'petition_id' => $this->petition->id,
            'type' => TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_WITHDRAWAL->value,
            'start_date' => $startDate->format('Y-m-d'),
            'duration_in_days' => $expectedDuration,
            'penalty_amount_in_euros' => 0,
            'parent_id' => null,
        ]);

        $this->assertDatabaseMissing('petition_terms', [
            'petition_id' => $this->petition->id,
            'type' => TermType::PENDING_TERM_AFTER_WITHDRAWAL->value,
        ]);

        $this->assertDatabaseMissing('petition_draft_terms', [
            'id' => $draftTerm->id,
        ]);
    }

    public function testDoesNotCreatePendingTermAfterEventWhenDaysAfterEventIsZero(): void
    {
        $startDate = $this->faker->calendarDate();
        $eventDate = $startDate->addDays(10);

        $draftTerm = PetitionDraftTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'start_date' => $startDate,
            'event_date' => $eventDate,
            'date_withdrawal' => null,
            'days_after_event' => 0,
            'days_after_date_withdrawal' => 14,
        ]);

        $baseDuration = TermDateCalculator::calculateDuration($startDate, $eventDate);
        $endDate = TermDateCalculator::calculateEndDate($startDate, $baseDuration);
        $deadlineCalculator = $this->app->make(LegalTermDeadlineCalculator::class);
        $adjustedEndDate = $deadlineCalculator->calculate($endDate);
        $expectedDuration = TermDateCalculator::calculateDuration($startDate, $adjustedEndDate);

        $this->service->convertDraftTermToPetitionTerms($draftTerm, $this->user);

        $this->assertDatabaseHas('petition_terms', [
            'petition_id' => $this->petition->id,
            'type' => TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_EVENT->value,
            'start_date' => $startDate->format('Y-m-d'),
            'duration_in_days' => $expectedDuration,
            'penalty_amount_in_euros' => 0,
            'parent_id' => null,
        ]);

        $this->assertDatabaseMissing('petition_terms', [
            ['petition_id' => $this->petition->id, 'type' => TermType::PENDING_TERM_AFTER_WITHDRAWAL->value],
        ]);

        $this->assertDatabaseMissing('petition_draft_terms', [
            ['id' => $draftTerm->id],
        ]);
    }

    public function testDoesNotCreatePendingTermAfterWithdrawalWhenDaysAfterWithdrawalIsZero(): void
    {
        $startDate = $this->faker->calendarDate();
        $withdrawalDate = $startDate->addDays(15);

        $draftTerm = PetitionDraftTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'start_date' => $startDate,
            'event_date' => null,
            'date_withdrawal' => $withdrawalDate,
            'days_after_event' => 30,
            'days_after_date_withdrawal' => 0,
        ]);
        $baseDuration = TermDateCalculator::calculateDuration($startDate, $withdrawalDate);
        $endDate = $startDate->addDays($baseDuration - 1);
        $deadlineCalculator = $this->app->make(LegalTermDeadlineCalculator::class);
        $adjustedEndDate = $deadlineCalculator->calculate($endDate);
        $expectedDuration = TermDateCalculator::calculateDuration($startDate, $adjustedEndDate);

        $this->service->convertDraftTermToPetitionTerms($draftTerm, $this->user);

        $this->assertDatabaseHas('petition_terms', [
            'petition_id' => $this->petition->id,
            'type' => TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_WITHDRAWAL->value,
            'start_date' => $startDate->format('Y-m-d'),
            'duration_in_days' => $expectedDuration,
            'penalty_amount_in_euros' => 0,
            'parent_id' => null,
        ]);

        $this->assertDatabaseMissing('petition_terms', [
            ['petition_id' => $this->petition->id, 'type' => TermType::PENDING_TERM_AFTER_WITHDRAWAL->value],
        ]);

        $this->assertDatabaseMissing('petition_draft_terms', [
            ['id' => $draftTerm->id],
        ]);
    }

    public function testUpdatesDeadlineWhenConvertingDraftTermToRealTerms(): void
    {
        $startDate = $this->faker->calendarDate();
        $eventDate = $startDate->addDays(10);

        PetitionTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'start_date' => $startDate->subDays(20),
            'end_date' => $startDate->subDay(),
            'duration_in_days' => 20,
            'type' => TermType::FIRST,
        ]);

        $draftTerm = PetitionDraftTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'start_date' => $startDate,
            'event_date' => $eventDate,
            'date_withdrawal' => null,
            'days_after_event' => 30,
            'days_after_date_withdrawal' => 14,
        ]);

        $petitionTermsUpdateAction = $this->app->make(PetitionTermsUpdateAction::class);
        $petitionTermsUpdateAction->execute($this->petition);

        $this->petition->refresh();
        $this->assertNull($this->petition->deadline_at);

        $this->service->convertDraftTermToPetitionTerms($draftTerm, $this->user);

        $this->petition->refresh();
        $this->assertNotNull($this->petition->deadline_at);

        $newTerms = $this->petition->petitionTerms->where('type', TermType::PENDING_TERM_AFTER_EVENT);
        $this->assertTrue($newTerms->isNotEmpty());

        $latestEndDate = $this->petition->petitionTerms->latestEndDate();
        $this->assertEquals($latestEndDate, $this->petition->deadline_at);
    }
}
