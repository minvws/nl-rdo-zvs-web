<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Database\Builder\Petition\Sorts;

use App\Enums\TermType;
use App\Models\Builder\Petition\Sorts\SumOfPenaltiesPerDateSort;
use App\Models\Petition;
use App\Models\PetitionTerm;
use App\ValueObjects\CalendarDate;
use Illuminate\Database\Eloquent\Builder;
use Ramsey\Uuid\Uuid;
use Tests\Feature\FeatureTestCase;

class SumOfPenaltiesPerDateSortTest extends FeatureTestCase
{
    public function testSortBySumOfPenaltiesPerDateAscending(): void
    {
        $today = CalendarDate::create('2025-01-15'); // Use fixed date

        $petition1 = Petition::factory()->create(['id' => Uuid::uuid7()]);
        PetitionTerm::factory()->for($petition1)->create([
            'type' => TermType::PENALTY,
            'start_date' => $today->subDays(10),
            'duration_in_days' => 20,
            'penalty_amount_in_euros' => 100,
        ]);

        $petition2 = Petition::factory()->create(['id' => Uuid::uuid7()]);
        PetitionTerm::factory()->for($petition2)->create([
            'type' => TermType::PENALTY,
            'start_date' => $today->subDays(5),
            'duration_in_days' => 20,
            'penalty_amount_in_euros' => 200,
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
        $today = CalendarDate::today();

        $petition1 = Petition::factory()->create(['id' => Uuid::uuid7()]);
        PetitionTerm::factory()->for($petition1)->create([
            'type' => TermType::PENALTY,
            'start_date' => $today->subDays(10),
            'duration_in_days' => 20,
            'penalty_amount_in_euros' => 100,
        ]);

        $petition2 = Petition::factory()->create(['id' => Uuid::uuid7()]);
        PetitionTerm::factory()->for($petition2)->create([
            'type' => TermType::PENALTY,
            'start_date' => $today->subDays(5),
            'duration_in_days' => 20,
            'penalty_amount_in_euros' => 200,
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
