<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Petition;

use App\Actions\Petition\PetitionCreateAction;
use App\Enums\PetitionTypeType;
use App\Models\Department;
use App\Models\PetitionEvent;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class PetitionCreateActionTest extends FeatureTestCase
{
    public function testDoesNotCreatePetitionEventsWhenTermEngineV2IsDisabled(): void
    {
        $this->app['config']->set('app.features.term_engine_v2', false);

        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()
            ->recycle($department)
            ->create(['type' => PetitionTypeType::WOO_VERZOEK]);
        PetitionStatus::factory()->recycle($petitionType)->create(['order' => 1]);
        $user = User::factory()->create();

        $action = $this->app->make(PetitionCreateAction::class);
        $petition = $action->execute($department, $user, $petitionType, [
            'date_of_entry' => '2024-01-01',
            'number' => 'TEST-2024-001',
        ]);

        $this->assertFalse(
            PetitionEvent::query()->where('petition_id', $petition->id)->exists(),
        );
    }

    public function testCreatesPetitionEventsWhenTermEngineV2IsEnabled(): void
    {
        $this->app['config']->set('app.features.term_engine_v2', true);
        $this->app['config']->set('petition_events.defaults.woo_verzoek.petition_received', ['duration' => 6]);

        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()
            ->recycle($department)
            ->create(['type' => PetitionTypeType::WOO_VERZOEK]);
        PetitionStatus::factory()->recycle($petitionType)->create(['order' => 1]);
        $user = User::factory()->create();

        $action = $this->app->make(PetitionCreateAction::class);
        $petition = $action->execute($department, $user, $petitionType, [
            'date_of_entry' => '2024-01-01',
            'number' => 'TEST-2024-002',
        ]);

        $this->assertTrue(
            PetitionEvent::query()->where('petition_id', $petition->id)->exists(),
        );
    }
}
