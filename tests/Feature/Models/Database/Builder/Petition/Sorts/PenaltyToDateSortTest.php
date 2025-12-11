<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Database\Builder\Petition\Sorts;

use App\Enums\TermType;
use App\Models\Builder\Petition\Sorts\PenaltyToDateSort;
use App\Models\Petition;
use App\Models\PetitionTerm;
use App\QueryBuilders\PetitionQueryBuilder;
use App\ValueObjects\CalendarDate;
use Illuminate\Database\Eloquent\Builder;
use Tests\Feature\FeatureTestCase;

class PenaltyToDateSortTest extends FeatureTestCase
{
    public function testSortByPenaltyToDateAscending(): void
    {
        $today = CalendarDate::today();

        $petition1 = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition1)->create([
            'type' => TermType::PENALTY,
            'start_date' => $today->subDays(5),
            'duration_in_days' => 10,
            'penalty_amount_in_euros' => 100,
        ]);

        $petition2 = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition2)->create([
            'type' => TermType::PENALTY,
            'start_date' => $today->subDays(10),
            'duration_in_days' => 20,
            'penalty_amount_in_euros' => 200,
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
        $today = CalendarDate::today();

        $petition1 = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition1)->create([
            'type' => TermType::PENALTY,
            'start_date' => $today->subDays(5),
            'duration_in_days' => 10,
            'penalty_amount_in_euros' => 100,
        ]);

        $petition2 = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition2)->create([
            'type' => TermType::PENALTY,
            'start_date' => $today->subDays(10),
            'duration_in_days' => 20,
            'penalty_amount_in_euros' => 200,
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
