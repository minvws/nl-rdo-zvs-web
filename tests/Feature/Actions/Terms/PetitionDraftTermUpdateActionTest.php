<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Terms;

use App\Actions\Terms\PetitionDraftTermUpdateAction;
use App\Enums\TermType;
use App\Enums\TimelineType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionDraftTerm;
use App\Models\PetitionTerm;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use Tests\Feature\FeatureTestCase;

class PetitionDraftTermUpdateActionTest extends FeatureTestCase
{
    private PetitionDraftTermUpdateAction $action;
    private User $user;
    private Department $department;
    private Petition $petition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->department = Department::factory()->create();
        $this->petition = Petition::factory()->recycle($this->department)->create(['deadline_at' => null]);

        $this->action = $this->app->make(PetitionDraftTermUpdateAction::class);
    }

    public function testUpdateDraftTermUpdatesDeadlineWhenNoDatesProvided(): void
    {
        $termStartDate = $this->faker->calendarDate();
        $termDurationInDays = 14;

        PetitionTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'start_date' => $termStartDate,
            'duration_in_days' => $termDurationInDays,
            'type' => TermType::FIRST,
        ]);

        $draftTerm = PetitionDraftTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'start_date' => $termStartDate->addDays($termDurationInDays),
            'description' => 'Original description',
        ]);

        $this->petition->update(['deadline_at' => CalendarDate::today()]);

        $this->action->execute($this->petition, $draftTerm, $this->user, [
            'description' => 'Updated description',
            'event_date' => null,
            'date_withdrawal' => null,
            'days_after_event' => 10,
            'days_after_date_withdrawal' => null,
        ]);

        $draftTerm->refresh();
        $this->assertEquals('Updated description', $draftTerm->description);
        $this->assertDatabaseHas('petition_draft_terms', [
            'id' => $draftTerm->id->toString(),
            'description' => 'Updated description',
        ]);

        $this->petition->refresh();
        $this->assertNull($this->petition->deadline_at);
    }

    public function testUpdateDraftTermCreatesTimelineEntry(): void
    {
        $draftTerm = PetitionDraftTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'start_date' => $this->faker->calendarDate(),
            'description' => 'Original description',
        ]);

        $this->action->execute($this->petition, $draftTerm, $this->user, [
            'description' => 'Updated description',
            'event_date' => null,
            'date_withdrawal' => null,
            'days_after_event' => 10,
            'days_after_date_withdrawal' => null,
        ]);

        $this->assertDatabaseHas('timeline_items', [
            'timelineable_id' => $this->petition->id->toString(),
            'timelineable_type' => $this->petition->getMorphClass(),
            'user_id' => $this->user->id->toString(),
            'type' => TimelineType::DRAFT_TERM_UPDATED->value,
        ]);
    }

    public function testUpdateDraftTermWithDatesConvertsToTerms(): void
    {
        $termStartDate = $this->faker->calendarDate();
        $termDurationInDays = 14;

        PetitionTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'start_date' => $termStartDate,
            'duration_in_days' => $termDurationInDays,
            'type' => TermType::FIRST,
        ]);

        $draftTerm = PetitionDraftTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'start_date' => $termStartDate->addDays($termDurationInDays),
            'description' => 'Original description',
        ]);

        $eventDate = $termStartDate->addDays($termDurationInDays + 5);
        $this->action->execute($this->petition, $draftTerm, $this->user, [
            'description' => 'Updated description',
            'event_date' => $eventDate->format('Y-m-d'),
            'date_withdrawal' => null,
            'days_after_event' => 10,
            'days_after_date_withdrawal' => null,
        ]);

        $this->assertDatabaseMissing('petition_draft_terms', [
            'id' => $draftTerm->id->toString(),
        ]);

        $this->petition->refresh();
        $this->assertNotNull($this->petition->deadline_at);

        $this->assertDatabaseHas('petition_terms', [
            'petition_id' => $this->petition->id->toString(),
            'type' => TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_EVENT->value,
        ]);

        $this->assertDatabaseHas('petition_terms', [
            'petition_id' => $this->petition->id->toString(),
            'type' => TermType::PENDING_TERM_AFTER_EVENT->value,
        ]);
    }
}
