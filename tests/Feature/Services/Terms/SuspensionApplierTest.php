<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Terms;

use App\Collections\PetitionTermCollection;
use App\Enums\TermType;
use App\Models\PetitionTerm;
use App\Services\Terms\SuspensionApplier;
use App\ValueObjects\CalendarDate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class SuspensionApplierTest extends FeatureTestCase
{
    /**
     * @param array<string, mixed> $suspensionsData
     */
    #[DataProvider('suspensionProvider')]
    public function testApplySuspensions(array $suspensionsData, string $expectedStartDate, string $expectedEndDate, TermType $termType): void
    {
        $term = PetitionTerm::factory()->create(
            [
                'type' => $termType,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
            ],
        );

        $collection = new PetitionTermCollection();

        foreach ($suspensionsData as $suspension) {
            $collection->push(PetitionTerm::factory()->make(
                [
                    'type' => TermType::SUSPENSION,
                    'start_date' => $suspension['start_date'],
                    'duration_in_days' => $suspension['duration_in_days'],
                ],
            ));
        }

        $expectedEndDate = CalendarDate::createFromFormat('d-m-Y', $expectedEndDate);
        $expectedStartDate = CalendarDate::createFromFormat('d-m-Y', $expectedStartDate);

        $suspensionApplier = new SuspensionApplier();
        $suspensionApplier->applySuspensions($term, $collection);

        $this->assertEquals($expectedStartDate, $term->start_date);
        $this->assertEquals($expectedEndDate, $term->end_date);
    }

    /**
     * @return array<string, mixed>
     */
    public static function suspensionProvider(): array
    {
        return [
            'no suspensions' => [
                [],
                '01-04-2025',
                '28-04-2025',
                TermType::FIRST,
            ],
            'one suspension' => [
                [
                    [
                        'start_date' => '2025-04-09',
                        'duration_in_days' => 2,
                    ],
                ],
                '01-04-2025',
                '30-04-2025',
                TermType::FIRST,
            ],
            'two suspensions' => [
                [
                    [
                        'start_date' => '2025-04-09',
                        'duration_in_days' => 2,
                    ],
                    [
                        'start_date' => '2025-04-30',
                        'duration_in_days' => 2,
                    ],
                ],
                '01-04-2025',
                '02-05-2025',
                TermType::FIRST,
            ],
            'three suspensions' => [
                [
                    [
                        'start_date' => '2025-04-09',
                        'duration_in_days' => 2,
                    ],
                    [
                        'start_date' => '2025-04-30',
                        'duration_in_days' => 2,
                    ],
                    [
                        'start_date' => '2025-04-15',
                        'duration_in_days' => 1,
                    ],
                ],
                '01-04-2025',
                '03-05-2025',
                TermType::FIRST,
            ],
            'one suspension with same start date' => [
                [
                    [
                        'start_date' => '2025-04-01',
                        'duration_in_days' => 2,
                    ],
                ],
                '03-04-2025',
                '30-04-2025',
                TermType::FIRST,
            ],
            'one suspension starts before, ends within' => [
                [
                    [
                        'start_date' => '2025-03-31',
                        'duration_in_days' => 3,
                    ],
                ],
                '03-04-2025',
                '30-04-2025',
                TermType::FIRST,
            ],
            'one suspension starts within, ends after end_date' => [
                [
                    [
                        'start_date' => '2025-03-21',
                        'duration_in_days' => 30,
                    ],
                ],
                '20-04-2025',
                '17-05-2025', // note: legal term is NOT applied here (will be done by the PetitionTermRecalculationService)!
                TermType::FIRST,
            ],
            'one suspension starts before start date and ends after' => [
                [
                    [
                        'start_date' => '2025-03-01',
                        'duration_in_days' => 40,
                    ],
                ],
                '10-04-2025',
                '07-05-2025',
                TermType::FIRST,
            ],
            'two suspensions, one starts before start date, one in the middle' => [
                [
                    [
                        'start_date' => '2025-03-01',
                        'duration_in_days' => 40,
                    ],
                    [
                        'start_date' => '2025-04-24',
                        'duration_in_days' => 5,
                    ],
                ],
                '10-04-2025',
                '12-05-2025',
                TermType::FIRST,
            ],
            'one suspension not applicable' => [
                [
                    [
                        'start_date' => '2025-04-01',
                        'duration_in_days' => 2,
                    ],
                ],
                '01-04-2025',
                '28-04-2025',
                TermType::NOTICE_OF_DEFAULT,
            ],
            'one suspension begins before and ends after first term' => [
                [
                    [
                        'start_date' => '2025-03-01',
                        'duration_in_days' => 100,
                    ],
                ],
                '09-06-2025',
                '06-07-2025',
                TermType::FIRST,
            ],
        ];
    }
}
