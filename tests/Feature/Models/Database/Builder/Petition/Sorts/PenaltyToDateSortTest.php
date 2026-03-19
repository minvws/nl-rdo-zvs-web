<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Database\Builder\Petition\Sorts;

use App\Models\Builder\Petition\Sorts\PenaltyToDateSort;
use App\Models\Petition;
use App\QueryBuilders\PetitionQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Tests\Feature\FeatureTestCase;

class PenaltyToDateSortTest extends FeatureTestCase
{
    public function testSortByPenaltyToDateAscending(): void
    {
        $petition1 = Petition::factory()->create([
            'legacy_term_forfeited' => 100,
            'igs_forfeited' => 0,
            'bnt_forfeited' => 0,
        ]);

        $petition2 = Petition::factory()->create([
            'legacy_term_forfeited' => 200,
            'igs_forfeited' => 0,
            'bnt_forfeited' => 0,
        ]);

        $sort = new PenaltyToDateSort();

        /** @var Builder<Petition> $query */
        $query = Petition::query()->withPenaltyToDate();
        $sort($query, false, 'penalty_to_date');

        $results = $query->get();

        $this->assertCount(2, $results);
        $this->assertEquals($petition1->id, $results->first()->id);
        $this->assertEquals($petition2->id, $results->last()->id);
    }

    public function testSortByPenaltyToDateDescending(): void
    {
        $petition1 = Petition::factory()->create([
            'legacy_term_forfeited' => 100,
            'igs_forfeited' => 0,
            'bnt_forfeited' => 0,
        ]);

        $petition2 = Petition::factory()->create([
            'legacy_term_forfeited' => 200,
            'igs_forfeited' => 0,
            'bnt_forfeited' => 0,
        ]);

        $sort = new PenaltyToDateSort();

        /** @var PetitionQueryBuilder $query */
        $query = Petition::query()->withPenaltyToDate();
        $sort($query, true, 'penalty_to_date');

        $results = $query->get();

        $this->assertCount(2, $results);
        $this->assertEquals($petition2->id, $results->first()->id);
        $this->assertEquals($petition1->id, $results->last()->id);
    }
}
