<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Terms;

use App\Enums\TermType;
use App\Models\Petition;
use App\Models\PetitionTerm;
use App\Models\PublicHoliday;
use App\Services\Terms\PetitionTermRecalculationService;
use App\ValueObjects\CalendarDate;
use Tests\Feature\FeatureTestCase;

class PetitionTermRecalculationServiceTest extends FeatureTestCase
{
    public function testWithOnlyFirst(): void // Use case 1
    {
        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '28-04-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $this->assertEquals($expectedEndDate, $petition->petitionTerms->where('type', TermType::FIRST)->first()->end_date);
    }

    public function testWithOnlyFirstAndWeekendAndHoliday(): void // Use case 2
    {
        PublicHoliday::factory()->create(['date' => '2025-05-05']);
        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '06-05-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 33,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $this->assertEquals($expectedEndDate, $petition->petitionTerms->where('type', TermType::FIRST)->first()->end_date);
    }

    public function testWithOnlyFirstAndOneSuspension(): void // Use case 3
    {
        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '02-05-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-04-15',
                'duration_in_days' => 4,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $this->assertEquals($expectedEndDate, $petition->petitionTerms->where('type', TermType::FIRST)->first()->end_date);
    }

    public function testWithOnlyFirstAndOneSuspensionInWeekendAndHoliday(): void // Use case 4
    {
        PublicHoliday::factory()->create(['date' => '2025-05-05']);
        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '06-05-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-04-15',
                'duration_in_days' => 6,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $this->assertEquals($expectedEndDate, $petition->petitionTerms->where('type', TermType::FIRST)->first()->end_date);
    }

    public function testWithOnlyFirstAndTwoSuspensions(): void // Use case 5
    {
        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '02-05-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-04-09',
                'duration_in_days' => 2,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-04-30',
                'duration_in_days' => 2,
            ],
        );


        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $this->assertEquals($expectedEndDate, $petition->petitionTerms->where('type', TermType::FIRST)->first()->end_date);
    }

    public function testWithOnlyFirstAndSecondSuspensionsAndWeekendAndHoliday(): void // Use case 6
    {
        PublicHoliday::factory()->create(['date' => '2025-05-05']);

        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '06-05-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-04-09',
                'duration_in_days' => 2,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-04-27',
                'duration_in_days' => 5,
            ],
        );


        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $this->assertEquals($expectedEndDate, $petition->petitionTerms->where('type', TermType::FIRST)->first()->end_date);
    }

    public function testWithOnlyFirstAndSecondAndWeekend(): void // Use case 7
    {
        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '12-05-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SECOND,
                'start_date' => '2025-04-29',
                'duration_in_days' => 12,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $this->assertEquals($expectedEndDate, $petition->petitionTerms->where('type', TermType::SECOND)->first()->end_date);
    }

    public function testWithOnlyFirstAndSecond(): void // Use case 8
    {
        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '12-05-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SECOND,
                'start_date' => '2025-04-29',
                'duration_in_days' => 14,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $this->assertEquals($expectedEndDate, $petition->petitionTerms->where('type', TermType::SECOND)->first()->end_date);
    }

    public function testWithOnlyFirstAndSecondNotExtended(): void // Use case 9
    {
        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '12-05-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 33,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SECOND,
                'start_date' => '2025-04-29',
                'duration_in_days' => 9,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $this->assertEquals($expectedEndDate, $petition->petitionTerms->where('type', TermType::SECOND)->first()->end_date);
    }

    public function testWithFirstAndSecondAndSuspension(): void // Use case 10
    {
        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '20-05-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SECOND,
                'start_date' => '2025-04-29',
                'duration_in_days' => 14,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-04-27',
                'duration_in_days' => 8,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $this->assertEquals($expectedEndDate, $petition->petitionTerms->where('type', TermType::SECOND)->first()->end_date);
    }

    public function testWithFirstAndSecondAndSuspensionWithWeekend(): void // Use case 11
    {
        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '19-05-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SECOND,
                'start_date' => '2025-04-29',
                'duration_in_days' => 14,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-04-27',
                'duration_in_days' => 5,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $this->assertEquals($expectedEndDate, $petition->petitionTerms->where('type', TermType::SECOND)->first()->end_date);
    }

    public function testWithFirstAndSecondAndSuspensionInSecond(): void // Use case 12
    {
        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '20-05-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SECOND,
                'start_date' => '2025-04-29',
                'duration_in_days' => 14,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-04-30',
                'duration_in_days' => 8,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $this->assertEquals($expectedEndDate, $petition->petitionTerms->where('type', TermType::SECOND)->first()->end_date);
    }

    public function testWithFirstAndSecondAndSuspensionInSecondWithWeekend(): void // Use case 13
    {
        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '19-05-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SECOND,
                'start_date' => '2025-04-29',
                'duration_in_days' => 14,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-04-30',
                'duration_in_days' => 5,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $this->assertEquals($expectedEndDate, $petition->petitionTerms->where('type', TermType::SECOND)->first()->end_date);
    }

    public function testWithFirstAndSecondAndSuspensionInSecondOnFirstDayWithWeekend(): void // Use case 14
    {
        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '19-05-2025');
        $expectedStartDate = CalendarDate::createFromFormat('d-m-Y', '04-05-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SECOND,
                'start_date' => '2025-04-29',
                'duration_in_days' => 14,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-04-29',
                'duration_in_days' => 5,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $this->assertEquals($expectedEndDate, $petition->petitionTerms->where('type', TermType::SECOND)->first()->end_date);
        $this->assertEquals($expectedStartDate, $petition->petitionTerms->where('type', TermType::SECOND)->first()->start_date);
    }

    public function testWithFirstSecondAndThird(): void // Use case 15
    {
        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '19-05-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SECOND,
                'start_date' => '2025-04-29',
                'duration_in_days' => 14,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::THIRD,
                'start_date' => '2025-05-13',
                'duration_in_days' => 7,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $this->assertEquals($expectedEndDate, $petition->petitionTerms->where('type', TermType::THIRD)->first()->end_date);
    }

    public function testWithFirstSecondAndThirdInAWeekend(): void // Use case 16
    {
        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '19-05-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SECOND,
                'start_date' => '2025-04-29',
                'duration_in_days' => 12,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::THIRD,
                'start_date' => '2025-05-13',
                'duration_in_days' => 7,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $this->assertEquals($expectedEndDate, $petition->petitionTerms->where('type', TermType::THIRD)->first()->end_date);
    }

    public function testWithFirstSecondAndThirdWithSuspensionInFirst(): void // Use case 17
    {
        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '23-05-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SECOND,
                'start_date' => '2025-04-29',
                'duration_in_days' => 14,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::THIRD,
                'start_date' => '2025-05-13',
                'duration_in_days' => 7,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-04-27',
                'duration_in_days' => 4,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);



        $this->assertEquals($expectedEndDate, $petition->petitionTerms->where('type', TermType::THIRD)->first()->end_date);
    }

    public function testWithFirstSecondAndThirdWithSuspensionInFirstAndWeekend(): void // Use case 18
    {
        $service = $this->termRecalculationService();
        $expectedEndDateFirst = CalendarDate::createFromFormat('d-m-Y', '04-05-2025');
        $expectedEndDateSecond = CalendarDate::createFromFormat('d-m-Y', '18-05-2025');
        $expectedEndDateThird = CalendarDate::createFromFormat('d-m-Y', '26-05-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SECOND,
                'start_date' => '2025-04-29',
                'duration_in_days' => 14,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::THIRD,
                'start_date' => '2025-05-13',
                'duration_in_days' => 7,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-04-27',
                'duration_in_days' => 6,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $this->assertEquals($expectedEndDateFirst, $petition->petitionTerms->where('type', TermType::FIRST)->first()->end_date);
        $this->assertEquals($expectedEndDateSecond, $petition->petitionTerms->where('type', TermType::SECOND)->first()->end_date);
        $this->assertEquals($expectedEndDateThird, $petition->petitionTerms->where('type', TermType::THIRD)->first()->end_date);
    }

    public function testWithFirstSecondAndThirdWithSuspensionInSecond(): void // Use case 19
    {
        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '22-05-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SECOND,
                'start_date' => '2025-04-29',
                'duration_in_days' => 14,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::THIRD,
                'start_date' => '2025-05-13',
                'duration_in_days' => 7,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-04-30',
                'duration_in_days' => 3,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $this->assertEquals($expectedEndDate, $petition->petitionTerms->where('type', TermType::THIRD)->first()->end_date);
    }

    public function testWithFirstSecondAndThirdWithSuspensionInSecondAndWeekend(): void // Use case 20
    {
        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '26-05-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SECOND,
                'start_date' => '2025-04-29',
                'duration_in_days' => 14,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::THIRD,
                'start_date' => '2025-05-13',
                'duration_in_days' => 7,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-04-30',
                'duration_in_days' => 5,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);



        $this->assertEquals($expectedEndDate, $petition->petitionTerms->where('type', TermType::THIRD)->first()->end_date);
    }

    public function testWithFirstSecondAndThirdWithSuspensionInThird(): void // Use case 21
    {
        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '23-05-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SECOND,
                'start_date' => '2025-04-29',
                'duration_in_days' => 12,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::THIRD,
                'start_date' => '2025-05-13',
                'duration_in_days' => 7,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-05-13',
                'duration_in_days' => 6,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);



        $this->assertEquals($expectedEndDate, $petition->petitionTerms->where('type', TermType::THIRD)->first()->end_date);
    }

    public function testWithFirstSecondAndThirdWithSuspensionInThirdAndWeekend(): void // Use case 22
    {
        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '26-05-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SECOND,
                'start_date' => '2025-04-29',
                'duration_in_days' => 12,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::THIRD,
                'start_date' => '2025-05-13',
                'duration_in_days' => 8,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-05-13',
                'duration_in_days' => 6,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $this->assertEquals($expectedEndDate, $petition->petitionTerms->where('type', TermType::THIRD)->first()->end_date);
    }

    public function testWithFirstSecondAndThirdWithSuspensionInAll(): void // Use case 23
    {
        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '28-05-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SECOND,
                'start_date' => '2025-04-29',
                'duration_in_days' => 14,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::THIRD,
                'start_date' => '2025-05-13',
                'duration_in_days' => 7,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-04-15',
                'duration_in_days' => 3,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-05-07',
                'duration_in_days' => 4,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-05-22',
                'duration_in_days' => 2,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $this->assertEquals($expectedEndDate, $petition->petitionTerms->where('type', TermType::THIRD)->first()->end_date);
    }

    public function testWithFirstSecondAndThirdWithSuspensionInAllAndHoliday(): void // Use case 24
    {
        PublicHoliday::factory()->create(['date' => '2025-05-29']);
        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '30-05-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SECOND,
                'start_date' => '2025-04-29',
                'duration_in_days' => 14,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::THIRD,
                'start_date' => '2025-05-13',
                'duration_in_days' => 7,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-04-15',
                'duration_in_days' => 3,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-05-07',
                'duration_in_days' => 4,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-05-22',
                'duration_in_days' => 3,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $this->assertEquals($expectedEndDate, $petition->petitionTerms->where('type', TermType::THIRD)->first()->end_date);
    }

    public function testWithFirstSecondAndThirdAndCommitteeHearingWithSuspensionInAllAndHoliday(): void // Use case 36
    {
        $service = $this->termRecalculationService();
        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', '22-09-2025');

        $petition = Petition::factory()->create();
        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-05-13',
                'duration_in_days' => 42,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::COMMITTEE_HEARING,
                'start_date' => '2025-05-14',
                'duration_in_days' => 42,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SECOND,
                'start_date' => '2025-08-08',
                'duration_in_days' => 42,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::SUSPENSION,
                'start_date' => '2025-09-03',
                'duration_in_days' => 5,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $this->assertEquals($expectedEndDate, $petition->petitionTerms->getLegalDateApplicableTerm()->end_date);
    }

    public function testPenaltiesAreAlignedWhenNoticeOfDefaultChanges(): void // Use case 36
    {
        $service = $this->termRecalculationService();
        $petition = Petition::factory()->create();

        $parent = PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::NOTICE_OF_DEFAULT,
                'start_date' => '2025-05-22',
                'duration_in_days' => 10,
            ],
        );

        $child1 = PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::PENALTY,
                'start_date' => '2025-01-02',
                'duration_in_days' => 14,
                'parent_id' => $parent->id,
            ],
        );

        $child2 = PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::PENALTY,
                'start_date' => '2025-01-02',
                'duration_in_days' => 14,
                'parent_id' => $child1->id,
            ],
        );

        PetitionTerm::factory()->recycle($petition)->create(
            [
                'type' => TermType::PENALTY,
                'start_date' => '2025-01-02',
                'duration_in_days' => 14,
                'parent_id' => $child2->id,
            ],
        );

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $this->assertEquals(
            $petition->petitionTerms->firstWhere('parent_id', $parent->id)->start_date,
            $petition->petitionTerms->firstWhere('id', $parent->id)->end_date->addDay(),
        );
        $this->assertEquals(
            $petition->petitionTerms->firstWhere('parent_id', $child1->id)->start_date,
            $petition->petitionTerms->firstWhere('id', $child1->id)->end_date->addDay(),
        );
        $this->assertEquals(
            $petition->petitionTerms->firstWhere('parent_id', $child2->id)->start_date,
            $petition->petitionTerms->firstWhere('id', $child2->id)->end_date->addDay(),
        );
    }

    private function termRecalculationService(): PetitionTermRecalculationService
    {
        return $this->app->make(PetitionTermRecalculationService::class);
    }

    public function testFirstTermStartDateNotAdjustedWhenObjectionPeriodDeadlineIsAdjustedDueToATW(): void
    {
        PublicHoliday::factory()->create(['date' => '2025-07-14']);

        $service = $this->termRecalculationService();

        $petition = Petition::factory()->create();

        PetitionTerm::factory()->recycle($petition)->create([
            'type' => TermType::OBJECTION_PERIOD,
            'start_date' => '2025-06-01',
            'duration_in_days' => 42,
        ]);

        PetitionTerm::factory()->recycle($petition)->create([
            'type' => TermType::FIRST,
            'start_date' => '2025-06-01',
            'duration_in_days' => 14,
        ]);

        $service->recalculate($petition->petitionTerms, $petition->date_of_entry);

        $objectionPeriodEndDate = $petition->petitionTerms->where('type', TermType::OBJECTION_PERIOD)->first()->end_date;

        $expectedObjectionPeriodEndDate = CalendarDate::createFromFormat('Y-m-d', '2025-07-15');
        $this->assertEquals(
            $expectedObjectionPeriodEndDate,
            $objectionPeriodEndDate,
            'Objection period deadline should be adjusted by ATW',
        );

        $firstTermStartDate = $petition->petitionTerms->where('type', TermType::FIRST)->first()->start_date;
        $expectedFirstTermStartDate = CalendarDate::createFromFormat('Y-m-d', '2025-07-16');

        $this->assertEquals(
            $expectedFirstTermStartDate,
            $firstTermStartDate,
            'First term should start 1 day after the ATW-adjusted objection period deadline',
        );
    }
}
