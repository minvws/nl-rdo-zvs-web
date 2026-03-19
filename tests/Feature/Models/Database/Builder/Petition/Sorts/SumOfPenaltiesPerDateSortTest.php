<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Database\Builder\Petition\Sorts;

use App\Models\Builder\Petition\Sorts\SumOfPenaltiesPerDateSort;
use App\Models\Petition;
use Illuminate\Database\Eloquent\Builder;
use Ramsey\Uuid\Uuid;
use Tests\Feature\FeatureTestCase;

class SumOfPenaltiesPerDateSortTest extends FeatureTestCase
{
    public function testSortBySumOfPenaltiesPerDateAscending(): void
    {
        $petition1 = Petition::factory()->create([
            'id' => Uuid::uuid7(),
            'legacy_term_penalty_today' => 100,
            'igs_penalty_today' => 0,
            'bnt_penalty_today' => 0,
        ]);

        $petition2 = Petition::factory()->create([
            'id' => Uuid::uuid7(),
            'legacy_term_penalty_today' => 200,
            'igs_penalty_today' => 0,
            'bnt_penalty_today' => 0,
        ]);

        $sort = new SumOfPenaltiesPerDateSort();

        /** @var Builder<Petition> $query */
        $query = Petition::query()->withSumOfPenaltiesPerDate();
        $sort($query, false, 'sum_of_penalties_per_date');

        $results = $query->get();

        $this->assertCount(2, $results);
        // Petition1 has lower penalty (100), so it should come first in ascending order
        $this->assertEquals($petition1->id, $results->first()->id);
        $this->assertEquals($petition2->id, $results->last()->id);
    }

    public function testSortBySumOfPenaltiesPerDateDescending(): void
    {
        $petition1 = Petition::factory()->create([
            'id' => Uuid::uuid7(),
            'legacy_term_penalty_today' => 100,
            'igs_penalty_today' => 0,
            'bnt_penalty_today' => 0,
        ]);

        $petition2 = Petition::factory()->create([
            'id' => Uuid::uuid7(),
            'legacy_term_penalty_today' => 200,
            'igs_penalty_today' => 0,
            'bnt_penalty_today' => 0,
        ]);

        $sort = new SumOfPenaltiesPerDateSort();

        /** @var Builder<Petition> $query */
        $query = Petition::query()->withSumOfPenaltiesPerDate();
        $sort($query, true, 'sum_of_penalties_per_date');

        $results = $query->get();

        $this->assertCount(2, $results);
        $this->assertEquals($petition2->id, $results->first()->id);
        $this->assertEquals($petition1->id, $results->last()->id);
    }
}
