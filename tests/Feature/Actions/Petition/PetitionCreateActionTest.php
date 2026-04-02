<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Petition;

use App\Actions\Petition\PetitionCreateAction;
use App\Enums\PetitionTypeType;
use App\Enums\TermType;
use App\Models\Department;
use App\Models\DepartmentTermTypeSetting;
use App\Models\PetitionEvent;
use App\Models\PetitionStatus;
use App\Models\PetitionTerm;
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

    public function testCreatesPetitionTermWhenTermEngineV2IsDisabled(): void
    {
        $this->app['config']->set('app.features.term_engine_v2', false);

        $department = Department::factory()->create();
        $durationInDays = 28;
        DepartmentTermTypeSetting::factory()->create([
            'department_id' => $department->id,
            'term_type' => TermType::FIRST,
            'field' => 'duration_in_days',
            'active' => true,
            'default_value' => $durationInDays,
        ]);
        DepartmentTermTypeSetting::factory()->create([
            'department_id' => $department->id,
            'term_type' => TermType::FIRST,
            'field' => 'penalty_amount_in_euros',
            'active' => true,
            'default_value' => 0,
        ]);
        $petitionType = PetitionType::factory()
            ->recycle($department)
            ->create(['type' => PetitionTypeType::WOO_VERZOEK]);
        PetitionStatus::factory()->recycle($petitionType)->create(['order' => 1]);
        $user = User::factory()->create();

        $action = $this->app->make(PetitionCreateAction::class);
        $petition = $action->execute($department, $user, $petitionType, [
            'date_of_entry' => '2025-01-01',
            'number' => 'TEST-2025-001',
        ]);

        $this->assertDatabaseHas(PetitionTerm::class, [
            'petition_id' => $petition->id,
            'type' => TermType::FIRST,
            'start_date' => '2025-01-02',
            'duration_in_days' => $durationInDays,
        ]);
    }
}
