<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Terms;

use App\Actions\Terms\PetitionDraftTermCreateAction;
use App\Enums\TermType;
use App\Enums\TimelineType;
use App\Exception\DomainException;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionDraftTerm;
use App\Models\PetitionTerm;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use Tests\Feature\FeatureTestCase;

use function json_encode;

class PetitionDraftTermCreateActionTest extends FeatureTestCase
{
    private PetitionDraftTermCreateAction $action;
    private User $user;
    private Department $department;
    private Petition $petitionWithoutTerms;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->department = Department::factory()->create();
        $this->petitionWithoutTerms = Petition::factory()->recycle($this->department)->create();

        $this->petitionWithoutTerms->petitionTerms()->delete();

        $this->action = $this->app->make(PetitionDraftTermCreateAction::class);
    }

    public function testExecuteThrowsDomainExceptionWhenPetitionHasNoExistingTerms(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot create draft term: petition must have at least one existing term');

        $attributes = [
            'description' => 'Test Draft Term',
            'event_date' => null,
            'days_after_event' => 0,
            'date_withdrawal' => null,
            'days_after_date_withdrawal' => null,
        ];

        $this->action->execute($this->petitionWithoutTerms, $this->user, $attributes);
    }

    public function testExecuteSuccessfullyCreatesDraftTermAndConvertsWhenDatesProvided(): void
    {
        $petitionWithTerms = Petition::factory()->recycle($this->department)->create();
        $latestTermEndDate = CalendarDate::createFromFormat('Y-m-d', '2024-03-15');
        PetitionTerm::factory()->create([
            'petition_id' => $petitionWithTerms->id,
            'start_date' => $latestTermEndDate->subDays(10),
            'end_date' => $latestTermEndDate,
            'duration_in_days' => 11,
            'type' => TermType::FIRST,
        ]);

        $attributes = [
            'description' => 'Test Draft Term with conversion',
            'event_date' => '2024-04-01', // Event date is present
            'days_after_event' => 10,
            'date_withdrawal' => null,
            'days_after_date_withdrawal' => null,
        ];

        $expectedDraftTermStartDate = $latestTermEndDate->addDay();

        $createdDraftTerm = $this->action->execute($petitionWithTerms, $this->user, $attributes);

        $this->assertInstanceOf(PetitionDraftTerm::class, $createdDraftTerm);

        $this->assertDatabaseMissing('petition_draft_terms', [
            'id' => $createdDraftTerm->id,
        ]);

        $this->assertDatabaseHas('petition_terms', [
            'petition_id' => $petitionWithTerms->id,
            'type' => TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_EVENT->value,
            'start_date' => $expectedDraftTermStartDate->format('Y-m-d'),
        ]);
        $this->assertDatabaseHas('petition_terms', [
            'petition_id' => $petitionWithTerms->id,
            'type' => TermType::PENDING_TERM_AFTER_EVENT->value,
            'start_date' => CalendarDate::createFromFormat('Y-m-d', $attributes['event_date'])->addDay()->format('Y-m-d'),
            'duration_in_days' => $attributes['days_after_event'],
        ]);

        $this->assertDatabaseHas('timeline_items', [
            'timelineable_id' => $petitionWithTerms->id,
            'timelineable_type' => (new Petition())->getMorphClass(),
            'user_id' => $this->user->id,
            'type' => TimelineType::DRAFT_TERM_CREATED->value,
            'data' => null,
        ]);
        // Also assert timeline items for created petition terms
        $this->assertDatabaseHas('timeline_items', [
            'timelineable_id' => $petitionWithTerms->id,
            'user_id' => $this->user->id,
            'type' => TimelineType::TERM_CREATED->value,
            'data' => json_encode(['term_type' => TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_EVENT->value]),
        ]);
        $this->assertDatabaseHas('timeline_items', [
            'timelineable_id' => $petitionWithTerms->id,
            'user_id' => $this->user->id,
            'type' => TimelineType::TERM_CREATED->value,
            'data' => json_encode(['term_type' => TermType::PENDING_TERM_AFTER_EVENT->value]),
        ]);
    }

    public function testExecuteSuccessfullyCreatesDraftTermWithoutConversionWhenDatesNotProvided(): void
    {
        $petitionWithTerms = Petition::factory()->recycle($this->department)->create();
        $latestTermEndDate = CalendarDate::createFromFormat('Y-m-d', '2024-05-20');
        PetitionTerm::factory()->create([
            'petition_id' => $petitionWithTerms->id,
            'start_date' => $latestTermEndDate->subDays(5),
            'end_date' => $latestTermEndDate,
            'duration_in_days' => 6,
            'type' => TermType::FIRST,
        ]);

        $attributes = [
            'description' => 'Test Draft Term no conversion',
            'event_date' => null,
            'days_after_event' => 0,
            'date_withdrawal' => null,
            'days_after_date_withdrawal' => null,
        ];

        $expectedStartDate = $latestTermEndDate->addDay();

        $createdDraftTerm = $this->action->execute($petitionWithTerms, $this->user, $attributes);

        $this->assertInstanceOf(PetitionDraftTerm::class, $createdDraftTerm);
        $this->assertEquals($petitionWithTerms->id, $createdDraftTerm->petition_id);
        $this->assertEquals($expectedStartDate->format('Y-m-d'), $createdDraftTerm->start_date->format('Y-m-d'));

        $this->assertDatabaseHas('petition_draft_terms', [
            'id' => $createdDraftTerm->id,
            'petition_id' => $petitionWithTerms->id,
            'start_date' => $expectedStartDate->format('Y-m-d'),
            'description' => $attributes['description'],
        ]);

        $this->assertDatabaseCount('petition_terms', 1 + PetitionTerm::where('petition_id', $this->petitionWithoutTerms->id)->count());


        $this->assertDatabaseHas('timeline_items', [
            'timelineable_id' => $petitionWithTerms->id,
            'timelineable_type' => (new Petition())->getMorphClass(),
            'user_id' => $this->user->id,
            'type' => TimelineType::DRAFT_TERM_CREATED->value,
            'data' => null,
        ]);

        $this->assertDatabaseMissing('timeline_items', [
            'timelineable_id' => $petitionWithTerms->id,
            'user_id' => $this->user->id,
            'type' => TimelineType::TERM_CREATED->value,
        ]);
    }
}
