<?php

declare(strict_types=1);

namespace Tests\Feature\Commands\Petition;

use App\Models\CustomPetitionProperty;
use App\Models\Decision;
use App\Models\Petition;
use App\Models\PetitionEvent;
use App\Models\PolicyDepartment;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Feature\FeatureTestCase;

class DeletePetitionsByNumberCommandTest extends FeatureTestCase
{
    public function testNoPetitionsFound(): void
    {
        $this->artisan('petition:delete-by-numbers', ['numbers' => ['9999999999']])
            ->expectsOutputToContain('9999999999')
            ->expectsOutput('No petitions found. Aborting.')
            ->assertFailed();
    }

    public function testWarnsAboutMissingNumbers(): void
    {
        $petition = Petition::factory()->create(['number' => '2025000001']);

        $this->artisan('petition:delete-by-numbers', ['numbers' => ['2025000001', '9999999999']])
            ->expectsOutputToContain('9999999999')
            ->expectsConfirmation(
                'Are you sure you want to PERMANENTLY delete these petitions and all related data? This cannot be undone.',
                'yes',
            )
            ->assertSuccessful();

        $this->assertDatabaseMissing(Petition::class, ['id' => $petition->id]);
    }

    public function testCancelledByUser(): void
    {
        $petition = Petition::factory()->create(['number' => '2025000001']);

        $this->artisan('petition:delete-by-numbers', ['numbers' => ['2025000001']])
            ->expectsConfirmation(
                'Are you sure you want to PERMANENTLY delete these petitions and all related data? This cannot be undone.',
                'no',
            )
            ->expectsOutput('Operation cancelled.')
            ->assertSuccessful();

        $this->assertDatabaseHas(Petition::class, ['id' => $petition->id]);
    }

    public function testDeletesPetitions(): void
    {
        $petition1 = Petition::factory()->create(['number' => '2025000001']);
        $petition2 = Petition::factory()->create(['number' => '2025000002']);
        $petition3 = Petition::factory()->create(['number' => '2025000003']);

        $this->artisan('petition:delete-by-numbers', ['numbers' => ['2025000001', '2025000002']])
            ->expectsConfirmation(
                'Are you sure you want to PERMANENTLY delete these petitions and all related data? This cannot be undone.',
                'yes',
            )
            ->expectsOutput('All petitions successfully deleted.')
            ->assertSuccessful();

        $this->assertDatabaseMissing(Petition::class, ['id' => $petition1->id]);
        $this->assertDatabaseMissing(Petition::class, ['id' => $petition2->id]);
        $this->assertDatabaseHas(Petition::class, ['id' => $petition3->id]);
    }

    public function testDeletesPetitionWithRelatedData(): void
    {
        $petition = Petition::factory()->create(['number' => '2025000001']);

        $event = PetitionEvent::factory()->create(['petition_id' => $petition->id]);
        $decision = Decision::factory()->create();
        $policyDepartment = PolicyDepartment::factory()->create();
        $customProperty = CustomPetitionProperty::factory()->create([
            'petition_type_id' => $petition->petition_type_id,
        ]);

        $petition->decisions()->attach($decision);
        $petition->policyDepartments()->attach($policyDepartment);
        $petition->customPetitionProperties()->attach($customProperty);

        $this->artisan('petition:delete-by-numbers', ['numbers' => ['2025000001']])
            ->expectsConfirmation(
                'Are you sure you want to PERMANENTLY delete these petitions and all related data? This cannot be undone.',
                'yes',
            )
            ->assertSuccessful();

        $this->assertDatabaseMissing(Petition::class, ['id' => $petition->id]);
        $this->assertDatabaseMissing(PetitionEvent::class, ['id' => $event->id]);
        $this->assertDatabaseMissing('decision_petition', ['petition_id' => $petition->id]);
        $this->assertDatabaseMissing('petition_policy_department', ['petition_id' => $petition->id]);
        $this->assertDatabaseMissing('custom_petition_property_petition', ['petition_id' => $petition->id]);
    }

    public function testHandlesTransactionFailure(): void
    {
        Petition::factory()->create(['number' => '2025000001']);

        DB::shouldReceive('transaction')->andThrow(new RuntimeException('boom'));

        $this->artisan('petition:delete-by-numbers', ['numbers' => ['2025000001']])
            ->expectsConfirmation(
                'Are you sure you want to PERMANENTLY delete these petitions and all related data? This cannot be undone.',
                'yes',
            )
            ->expectsOutputToContain('Error during deletion: boom')
            ->assertFailed();
    }
}
