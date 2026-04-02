<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PetitionTypeType;
use App\Enums\StatusGroup;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionCategory;
use App\Models\PetitionStatus;
use App\Models\PetitionStatusHistory;
use App\Models\PetitionType;
use Illuminate\Database\Seeder;

use function now;

class MismatchedPetitionStatusSeeder extends Seeder
{
    public function run(): void
    {
        $department = Department::factory()->create([
            'name' => 'Test mismatch department',
            'slug' => 'test-mismatch',
            'config_key' => 'test-mismatch',
            'abbreviation' => 'TM',
        ]);

        $petitionType = PetitionType::factory()->recycle($department)->create([
            'name' => 'Woo verzoek',
            'type' => PetitionTypeType::WOO_VERZOEK->value,
        ]);

        // statusA = petition's current status (the "wrong" one)
        $statusA = PetitionStatus::factory()->recycle($petitionType)->create([
            'status' => 'Status A (petition record)',
            'order' => 1,
            'status_group' => StatusGroup::PENDING,
        ]);

        // statusB = what history says it should be
        $statusB = PetitionStatus::factory()->recycle($petitionType)->create([
            'status' => 'Status B (history entry)',
            'order' => 2,
            'status_group' => StatusGroup::FINISHED,
        ]);

        $petitionCategories = PetitionCategory::factory()->count(3)->recycle($department)->create();

        Petition::factory()
            ->count(10)
            ->recycle([$department, $petitionType, $petitionCategories])
            ->create(['petition_status_id' => $statusA->id])
            ->each(function (Petition $petition) use ($statusA, $statusB): void {
                // Older entry with statusA (not the most recent)
                $old = now()->subDays(10);
                PetitionStatusHistory::factory()->create([
                    'petition_id' => $petition->id,
                    'petition_status_id' => $statusA->id,
                    'created_at' => $old,
                    'date' => $old->format('Y-m-d'),
                ]);

                // Most recent non-future entry — has statusB → causes mismatch
                $recent = now()->subDay();
                PetitionStatusHistory::factory()->create([
                    'petition_id' => $petition->id,
                    'petition_status_id' => $statusB->id,
                    'created_at' => $recent,
                    'date' => $recent->format('Y-m-d'),
                ]);
            });
    }
}
