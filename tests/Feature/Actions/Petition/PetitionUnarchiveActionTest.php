<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Petition;

use App\Actions\Petition\PetitionUnarchiveAction;
use App\Actions\PetitionEvent\Contracts\UpdatePetitionTotalsFromEventsActionInterface;
use App\Actions\PetitionEvent\Contracts\UpdatePetitionTotalsFromTermsActionInterface;
use App\Enums\TimelineType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\TimelineItem;
use App\Models\User;
use Carbon\Carbon;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class PetitionUnarchiveActionTest extends FeatureTestCase
{
    #[Test]
    public function testUnarchivePetition(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create([
            'archived_at' => Carbon::now()->subDay(),
        ]);

        $this->assertNotNull($petition->archived_at);

        $eventsAction = Mockery::mock(UpdatePetitionTotalsFromEventsActionInterface::class);
        $eventsAction->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(static fn (Petition $arg): bool => $arg->is($petition)));
        $this->app->instance(UpdatePetitionTotalsFromEventsActionInterface::class, $eventsAction);

        $termsAction = Mockery::mock(UpdatePetitionTotalsFromTermsActionInterface::class);
        $termsAction->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(static fn (Petition $arg): bool => $arg->is($petition)));
        $this->app->instance(UpdatePetitionTotalsFromTermsActionInterface::class, $termsAction);

        $action = $this->app->make(PetitionUnarchiveAction::class);

        $action->execute($petition, $user);

        $petition->refresh();

        $this->assertNull($petition->archived_at);

        $this->assertDatabaseHas(TimelineItem::class, [
            'timelineable_id' => $petition->id,
            'timelineable_type' => 'petition',
            'type' => TimelineType::PETITION_UNARCHIVED,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function testUnarchiveAlreadyUnarchivedPetitionDoesNothing(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create([
            'archived_at' => null,
        ]);

        $eventsAction = Mockery::mock(UpdatePetitionTotalsFromEventsActionInterface::class);
        $eventsAction->shouldNotReceive('execute');
        $this->app->instance(UpdatePetitionTotalsFromEventsActionInterface::class, $eventsAction);

        $termsAction = Mockery::mock(UpdatePetitionTotalsFromTermsActionInterface::class);
        $termsAction->shouldNotReceive('execute');
        $this->app->instance(UpdatePetitionTotalsFromTermsActionInterface::class, $termsAction);

        $action = $this->app->make(PetitionUnarchiveAction::class);

        $action->execute($petition, $user);

        $petition->refresh();

        $this->assertNull($petition->archived_at);

        // Should not create a timeline item for already unarchived petition
        $this->assertDatabaseMissing(TimelineItem::class, [
            'timelineable_id' => $petition->id,
            'timelineable_type' => 'petition',
            'type' => TimelineType::PETITION_UNARCHIVED,
            'user_id' => $user->id,
        ]);
    }
}
