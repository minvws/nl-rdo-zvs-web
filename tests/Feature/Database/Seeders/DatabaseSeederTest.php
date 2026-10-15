<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Seeders;

use App\Enums\PetitionVariant;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionStatus;
use App\Models\PetitionStatusHistory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function array_column;
use function collect;
use function database_path;

use const JSON_THROW_ON_ERROR;

class DatabaseSeederTest extends FeatureTestCase
{
    #[Test]
    public function testSeedsPetitionStatusesFromTheSeedData(): void
    {
        $this->seed(DatabaseSeeder::class);

        $expected = collect(File::json(database_path('seeders/data/petition-statuses.json'), JSON_THROW_ON_ERROR))
            ->map(static fn (array $row): array => [
                'id' => $row['id'],
                'petition_type_id' => $row['petition_type_id'],
                'status' => $row['status'],
                'order' => $row['order'],
                'status_group' => $row['status_group'],
            ])
            ->sortBy('id')
            ->values()
            ->all();

        $actual = PetitionStatus::query()
            ->whereIn('id', array_column($expected, 'id'))
            ->get()
            ->map(static fn (PetitionStatus $petitionStatus): array => [
                'id' => (string) $petitionStatus->id,
                'petition_type_id' => (string) $petitionStatus->petition_type_id,
                'status' => $petitionStatus->status,
                'order' => $petitionStatus->order,
                'status_group' => $petitionStatus->status_group->value,
            ])
            ->sortBy('id')
            ->values()
            ->all();

        $this->assertSame($expected, $actual);
    }

    #[Test]
    public function testSeedsKlachtPetitionTypeWithDefaultStatusForTeamWjzKlachten(): void
    {
        $this->seed(DatabaseSeeder::class);

        $department = Department::query()->where('slug', 'team-wjz-klachten')->firstOrFail();
        $petitionType = $department->petitionTypes()->where('type', PetitionVariant::KLACHT->value)->firstOrFail();

        $this->assertSame(
            ['Standaard status'],
            PetitionStatus::query()->where('petition_type_id', $petitionType->id)->pluck('status')->all(),
        );
    }

    #[Test]
    public function testPetitionsOnlyUseStatusesOfTheirOwnPetitionType(): void
    {
        $this->seed(DatabaseSeeder::class);

        Petition::query()->with('petitionStatus')->each(function (Petition $petition): void {
            $this->assertTrue($petition->petitionStatus->petition_type_id->equals($petition->petition_type_id));
        });
        PetitionStatusHistory::query()
            ->with(['petition', 'petitionStatus'])
            ->each(function (PetitionStatusHistory $history): void {
                $this->assertTrue(
                    $history->petitionStatus->petition_type_id->equals($history->petition->petition_type_id),
                );
            });
    }
}
