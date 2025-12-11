<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Database\Builder;

use App\Enums\StatusGroup;
use App\Models\Builder\Petition\Filters\PetitionStatusHistoryDateRangeFilter;
use App\Models\Petition;
use App\Models\PetitionStatus;
use App\Models\PetitionStatusHistory;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\DateRange;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\Test;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Tests\Feature\FeatureTestCase;

class PetitionStatusHistoryDateRangeFilterTest extends FeatureTestCase
{
    private CalendarDate $dateFrom;
    private CalendarDate $dateTo;
    private PetitionStatus $nonPendingStatus;
    private PetitionStatus $pendingStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dateFrom = CalendarDate::create('2025-01-07');
        $this->dateTo = CalendarDate::create('2025-01-18');

        $this->nonPendingStatus = PetitionStatus::factory()->create(['status_group' => StatusGroup::INTAKE]);
        $this->pendingStatus = PetitionStatus::factory()->create(['status_group' => StatusGroup::PENDING]);
    }

    #[Test]
    public function testPetitionWithNoPendingStateNotInSelection(): void
    {
        $petition1 = Petition::factory()->recycle($this->nonPendingStatus)->create(['number' => 'zaak 1']);
        PetitionStatusHistory::factory()->recycle($petition1)->recycle($this->nonPendingStatus)->create(
            [
                'created_at' => '2025-01-01 10:22:51.000000 +00:00',
                'date' => '2025-01-01',
            ],
        );

        $result = $this->queryBuilder(new DateRange($this->dateFrom, $this->dateTo))
            ->get()
            ->pluck('number');

        $this->assertFalse($result->contains('zaak 1'));
    }

    #[Test]
    public function testPetitionWithPendingStateAfterEndDateNotInSelection(): void
    {
        $petition2 = Petition::factory()->recycle($this->nonPendingStatus)->create(['number' => 'zaak 2']);
        PetitionStatusHistory::factory()->recycle($petition2)->recycle($this->nonPendingStatus)->create(
            [
                'created_at' => '2025-01-01 10:22:51.000000 +00:00',
                'date' => '2025-01-01',
            ],
        );
        PetitionStatusHistory::factory()->recycle($petition2)->recycle($this->pendingStatus)->create(
            [
                'created_at' => '2025-01-19 10:22:51.000000 +00:00',
                'date' => '2025-01-19',
            ],
        );

        $result = $this->queryBuilder(new DateRange($this->dateFrom, $this->dateTo))
            ->get()
            ->pluck('number');

        $this->assertFalse($result->contains('zaak 2'));
    }

    #[Test]
    public function testPetitionWithPendingStateBeforeEndDateInSelection(): void
    {
        $petition3 = Petition::factory()->recycle($this->nonPendingStatus)->create(['number' => 'zaak 3']);
        PetitionStatusHistory::factory()->recycle($petition3)->recycle($this->nonPendingStatus)->create(
            [
                'created_at' => '2025-01-01 10:22:51.000000 +00:00',
                'date' => '2025-01-01',
            ],
        );
        PetitionStatusHistory::factory()->recycle($petition3)->recycle($this->pendingStatus)->create(
            [
                'created_at' => '2025-01-16 10:22:51.000000 +00:00',
                'date' => '2025-01-16',
            ],
        );
        $result = $this->queryBuilder(new DateRange($this->dateFrom, $this->dateTo))
            ->get()
            ->pluck('number');

        $this->assertTrue($result->contains('zaak 3'));
    }

    #[Test]
    public function testPetitionWithPendingStateBetweenDatesInSelection(): void
    {
        $petition4 = Petition::factory()->recycle($this->nonPendingStatus)->create(['number' => 'zaak 4']);
        PetitionStatusHistory::factory()->recycle($petition4)->recycle($this->nonPendingStatus)->create(
            [
                'created_at' => '2025-01-01 10:22:51.000000 +00:00',
                'date' => '2025-01-01',
            ],
        );
        PetitionStatusHistory::factory()->recycle($petition4)->recycle($this->pendingStatus)->create(
            [
                'created_at' => '2025-01-08 10:22:51.000000 +00:00',
                'date' => '2025-01-08',
            ],
        );
        PetitionStatusHistory::factory()->recycle($petition4)->recycle($this->nonPendingStatus)->create(
            [
                'created_at' => '2025-01-16 10:22:51.000000 +00:00',
                'date' => '2025-01-16',
            ],
        );

        $result = $this->queryBuilder(new DateRange($this->dateFrom, $this->dateTo))
            ->get()
            ->pluck('number');

        $this->assertTrue($result->contains('zaak 4'));
    }

    #[Test]
    public function testPetitionWithPendingStateBetweenDatesInSelectionPart2(): void
    {
        $petition5 = Petition::factory()->recycle($this->nonPendingStatus)->create(['number' => 'zaak 5']);
        PetitionStatusHistory::factory()->recycle($petition5)->recycle($this->nonPendingStatus)->create(
            [
                'created_at' => '2025-01-01 10:22:51.000000 +00:00',
                'date' => '2025-01-01',
            ],
        );
        PetitionStatusHistory::factory()->recycle($petition5)->recycle($this->pendingStatus)->create(
            [
                'created_at' => '2025-01-07 00:00:00.000000 +00:00',
                'date' => '2025-01-07',
            ],
        );
        PetitionStatusHistory::factory()->recycle($petition5)->recycle($this->nonPendingStatus)->create(
            [
                'created_at' => '2025-01-15 10:22:51.000000 +00:00',
                'date' => '2025-01-15',
            ],
        );

        $result = $this->queryBuilder(new DateRange($this->dateFrom, $this->dateTo))
            ->get()
            ->pluck('number');

        $this->assertTrue($result->contains('zaak 5'));
    }

    #[Test]
    public function testPetitionWithPendingStateBeforeAndAfterStartDateInSelection(): void
    {
        $petition6 = Petition::factory()->recycle($this->nonPendingStatus)->create(['number' => 'zaak 6']);
        PetitionStatusHistory::factory()->recycle($petition6)->recycle($this->nonPendingStatus)->create(
            [
                'created_at' => '2025-01-01 10:22:51.000000 +00:00',
                'date' => '2025-01-01',
            ],
        );
        PetitionStatusHistory::factory()->recycle($petition6)->recycle($this->pendingStatus)->create(
            [
                'created_at' => '2025-01-05 00:00:00.000000 +00:00',
                'date' => '2025-01-05',
            ],
        );
        PetitionStatusHistory::factory()->recycle($petition6)->recycle($this->nonPendingStatus)->create(
            [
                'created_at' => '2025-01-11 10:22:51.000000 +00:00',
                'date' => '2025-01-11',
            ],
        );

        $result = $this->queryBuilder(new DateRange($this->dateFrom, $this->dateTo))
            ->get()
            ->pluck('number');

        $this->assertTrue($result->contains('zaak 6'));
    }

    #[Test]
    public function testPetitionWithPendingStateOnlyBeforeStartDateNotInSelection(): void
    {
        $petition7 = Petition::factory()->recycle($this->nonPendingStatus)->create(['number' => 'zaak 7']);
        PetitionStatusHistory::factory()->recycle($petition7)->recycle($this->nonPendingStatus)->create(
            [
                'created_at' => '2025-01-01 10:22:51.000000 +00:00',
                'date' => '2025-01-01',
            ],
        );
        PetitionStatusHistory::factory()->recycle($petition7)->recycle($this->pendingStatus)->create(
            [
                'created_at' => '2025-01-02 00:00:00.000000 +00:00',
                'date' => '2025-01-02',
            ],
        );
        PetitionStatusHistory::factory()->recycle($petition7)->recycle($this->nonPendingStatus)->create(
            [
                'created_at' => '2025-01-06 23:00:00.000000 +00:00',
                'date' => '2025-01-07',
            ], // 2025-01-07 00:00:00.000000 +01:00
        );

        $result = $this->queryBuilder(new DateRange($this->dateFrom, $this->dateTo))
            ->get()
            ->pluck('number');

        $this->assertTrue($result->contains('zaak 7'));
    }

    #[Test]
    public function testPetitionWithPendingStateOnlyBeforeStartDateInSelection(): void
    {
        $petition8 = Petition::factory()->recycle($this->pendingStatus)->create(['number' => 'zaak 8']);
        PetitionStatusHistory::factory()->recycle($petition8)->recycle($this->pendingStatus)->create(
            [
                'created_at' => '2025-01-02 00:00:00.000000 +00:00',
                'date' => '2025-01-02',
            ],
        );

        $result = $this->queryBuilder(new DateRange($this->dateFrom, $this->dateTo))
            ->get()
            ->pluck('number');

        $this->assertTrue($result->contains('zaak 8'));
    }

    #[Test]
    public function testPetitionWithPendingStateOnlyBeforeStartDateAndChangesBeforeStartDateNotInSelection(): void
    {
        $petition9 = Petition::factory()->recycle($this->pendingStatus)->create(['number' => 'zaak 9']);
        PetitionStatusHistory::factory()->recycle($petition9)->recycle($this->pendingStatus)->create(
            [
                'created_at' => '2023-01-02 00:00:00.000000 +00:00',
                'date' => '2023-01-02',
            ],
        );
        PetitionStatusHistory::factory()->recycle($petition9)->recycle($this->nonPendingStatus)->create(
            [
                'created_at' => '2024-01-02 00:00:00.000000 +00:00',
                'date' => '2024-01-02',
            ],
        );

        $result = $this->queryBuilder(new DateRange($this->dateFrom, $this->dateTo))
            ->get()
            ->pluck('number');

        $this->assertFalse($result->contains('zaak 9'));
    }

    #[Test]
    public function testBugMultipleStatusChangesOnSameDateReversedOrderUsesCreatedAt(): void
    {
        $petition = Petition::factory()->recycle($this->nonPendingStatus)->create(['number' => 'bug zaak reversed']);

        PetitionStatusHistory::factory()->recycle($petition)->recycle($this->pendingStatus)->create([
            'created_at' => '2025-01-06 14:00:00.000000 +00:00',
            'date' => '2025-01-06',
        ]);

        PetitionStatusHistory::factory()->recycle($petition)->recycle($this->nonPendingStatus)->create([
            'created_at' => '2025-01-06 10:00:00.000000 +00:00',
            'date' => '2025-01-06',
        ]);

        $result = $this->queryBuilder(new DateRange($this->dateFrom, $this->dateTo))
            ->get()
            ->pluck('number');

        $this->assertTrue(
            $result->contains('bug zaak reversed'),
            'Petition should be in result as the chronologically last status change (14:00) was to pending. ' .
            'This test demonstrates the bug where multiple status changes on the same date ' .
            'are not properly ordered by created_at.',
        );
    }

    #[Test]
    public function testBugOppositeScenarioNonPendingLastShouldNotBeInResult(): void
    {
        $petition = Petition::factory()->recycle($this->nonPendingStatus)->create(['number' => 'opposite bug zaak']);

        PetitionStatusHistory::factory()->recycle($petition)->recycle($this->pendingStatus)->create([
            'created_at' => '2025-01-06 10:00:00.000000 +00:00',
            'date' => '2025-01-06',
        ]);

        PetitionStatusHistory::factory()->recycle($petition)->recycle($this->nonPendingStatus)->create([
            'created_at' => '2025-01-06 14:00:00.000000 +00:00',
            'date' => '2025-01-06',
        ]);

        $result = $this->queryBuilder(new DateRange($this->dateFrom, $this->dateTo))
            ->get()
            ->pluck('number');

        $this->assertFalse(
            $result->contains('opposite bug zaak'),
            'Petition should NOT be in result as the chronologically last status change (14:00) was to non-pending. ' .
            'This test demonstrates the bug where multiple status changes on the same date ' .
            'are not properly ordered by created_at.',
        );
    }

    private function queryBuilder(DateRange $dateRange): Builder
    {
        return QueryBuilder::for(Petition::class)
            ->allowedFilters([
                AllowedFilter::custom('dateRange', new PetitionStatusHistoryDateRangeFilter())
                    ->default($dateRange),
            ])
            ->getEloquentBuilder();
    }
}
