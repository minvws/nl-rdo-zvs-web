<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Terms;

use App\Actions\Terms\PetitionDraftTermDeleteAction;
use App\Enums\TermType;
use App\Enums\TimelineType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionDraftTerm;
use App\Models\PetitionTerm;
use App\Models\User;
use App\Services\LegalTermDeadlineCalculator;
use App\Services\Terms\TermDateCalculator;
use Tests\Feature\FeatureTestCase;

class PetitionDraftTermDeleteActionTest extends FeatureTestCase
{
    private PetitionDraftTermDeleteAction $action;
    private User $user;
    private Department $department;
    private Petition $petition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = $this->app->make(PetitionDraftTermDeleteAction::class);
        $this->user = User::factory()->create();
        $this->department = Department::factory()->create();
        $this->petition = Petition::factory()->recycle($this->department)->create(['deadline_at' => null],);
    }

    public function testDeleteDraftTermUpdatesDeadlineFromNullToTermDeadlineIfTermsExist(): void
    {
        $termStartDate = $this->faker->calendarDate();
        $termDurationInDays = 14;

        $basicEndDate = TermDateCalculator::calculateEndDate($termStartDate, $termDurationInDays);
        $legalDeadlineCalculator = $this->app->make(LegalTermDeadlineCalculator::class);
        $expectedDeadline = $legalDeadlineCalculator->calculate($basicEndDate);

        PetitionTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'start_date' => $termStartDate,
            'duration_in_days' => $termDurationInDays,
            'type' => TermType::FIRST,
        ]);

        $draftTerm = PetitionDraftTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'start_date' => $termStartDate->addDays($termDurationInDays),
        ]);

        $this->action->execute($this->petition, $draftTerm, $this->user);

        $this->assertDatabaseMissing('petition_draft_terms', [
            'id' => $draftTerm->id->toString(),
        ]);

        $this->petition->refresh();
        $this->assertNotNull($this->petition->deadline_at);
        $this->assertTrue($this->petition->deadline_at->equals($expectedDeadline));
    }

    public function testDeleteDraftTermCreatesTimelineEntry(): void
    {
        $draftTerm = PetitionDraftTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'start_date' => $this->faker->calendarDate(),
        ]);

        $this->action->execute($this->petition, $draftTerm, $this->user);

        $this->assertDatabaseHas('timeline_items', [
            'timelineable_id' => $this->petition->id->toString(),
            'timelineable_type' => $this->petition->getMorphClass(),
            'user_id' => $this->user->id->toString(),
            'type' => TimelineType::DRAFT_TERM_DELETED->value,
        ]);
    }

    public function testDeleteDraftTermWithoutExistingTermsSetsSDateOfEntry(): void
    {
        $dateOfEntry = $this->faker->calendarDate();

        $petition = Petition::factory()->create([
            'date_of_entry' => $dateOfEntry,
            'deadline_at' => null,
        ]);

        $draftTerm = PetitionDraftTerm::factory()->create([
            'petition_id' => $petition->id,
            'start_date' => $this->faker->calendarDate(),
        ]);

        $this->action->execute($petition, $draftTerm, $this->user);

        $petition->refresh();
        $this->assertNotNull($petition->deadline_at);
        $this->assertTrue($dateOfEntry->equals($petition->deadline_at));
    }
}
