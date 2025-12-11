<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Terms;

use App\Collections\PetitionTermCollection;
use App\Enums\TermType;
use App\Models\PetitionTerm;
use App\ValueObjects\CalendarDate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class PetitionTermCollectionTest extends FeatureTestCase
{
    public function testTotalPenaltyAmountNonePenaltyTerm(): void
    {
        $collection = new PetitionTermCollection();

        $collection->push(PetitionTerm::factory()->make(
            [
                'type' => TermType::FIRST,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
                'penalty_amount_in_euros' => 100,
            ],
        ));

        $this->assertEquals(0, $collection->totalPenalty());
    }

    public function testTotalPenaltyAmountOneTerm(): void
    {
        $collection = new PetitionTermCollection();

        $collection->push(PetitionTerm::factory()->make(
            [
                'type' => TermType::PENALTY,
                'start_date' => '2025-04-01',
                'duration_in_days' => 28,
                'penalty_amount_in_euros' => 100,
            ],
        ),);

        $this->assertEquals(2800, $collection->totalPenalty());
    }

    public function testTotalPenaltyAmountTwoTerms(): void
    {
        $collection = new PetitionTermCollection();

        $collection->push(
            PetitionTerm::factory()->make(
                [
                    'type' => TermType::PENALTY,
                    'start_date' => '2025-04-01',
                    'duration_in_days' => 28,
                    'penalty_amount_in_euros' => 100,
                ],
            ),
            PetitionTerm::factory()->make(
                [
                    'type' => TermType::PENALTY,
                    'start_date' => '2025-05-01',
                    'duration_in_days' => 10,
                    'penalty_amount_in_euros' => 100,
                ],
            ),
        );

        $this->assertEquals(3800, $collection->totalPenalty());
    }

    public function testToDatePenaltyAmountPartialFutureTerms(): void
    {
        $date = CalendarDate::createFromFormat('Y-m-d', '2025-04-15');

        $collection = new PetitionTermCollection();

        $collection->push(
            PetitionTerm::factory()->make(
                [
                    'type' => TermType::PENALTY,
                    'start_date' => '2025-03-01',
                    'duration_in_days' => 28,
                    'penalty_amount_in_euros' => 100,
                ],
            ),
            PetitionTerm::factory()->make(
                [
                    'type' => TermType::PENALTY,
                    'start_date' => '2025-04-10',
                    'duration_in_days' => 28,
                    'penalty_amount_in_euros' => 100,
                ],
            ),
            PetitionTerm::factory()->make(
                [
                    'type' => TermType::PENALTY,
                    'start_date' => '2025-05-01',
                    'duration_in_days' => 10,
                    'penalty_amount_in_euros' => 100,
                ],
            ),
        );

        $this->assertEquals(3400, $collection->penaltyToDate($date));
    }

    public function testToDatePenaltyAmountAndFutureTerms(): void
    {
        $date = CalendarDate::createFromFormat('Y-m-d', '2024-04-15');

        $collection = new PetitionTermCollection();

        $collection->push(
            PetitionTerm::factory()->make(
                [
                    'type' => TermType::PENALTY,
                    'start_date' => '2025-04-01',
                    'duration_in_days' => 28,
                    'penalty_amount_in_euros' => 100,
                ],
            ),
            PetitionTerm::factory()->make(
                [
                    'type' => TermType::PENALTY,
                    'start_date' => '2025-05-01',
                    'duration_in_days' => 10,
                    'penalty_amount_in_euros' => 100,
                ],
            ),
        );

        $this->assertEquals(0, $collection->penaltyToDate($date));
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('collections')]
    public function testDeadline(string $expected, array $data): void
    {
        $duration = 28;
        $collection = new PetitionTermCollection();

        $expected = CalendarDate::createFromFormat('Y-m-d', $expected);

        foreach ($data as $item) {
            $collection->push(
                PetitionTerm::factory()->create([
                    'type' => $item['type'],
                    'start_date' => $expected->subDays($duration - 1),
                    'duration_in_days' => $duration,
                ]),
            );
        }

        $this->assertTrue($collection->deadline()->equals($expected));
    }

    public static function collections(): iterable
    {
        return [
            'one_term_applicable' => [
                '2025-04-28',
                [
                    [
                        'type' => TermType::NOTICE_OF_DEFAULT,
                        'end_date' => '2025-04-28',
                    ],
                    [
                        'type' => TermType::PENALTY,
                        'end_date' => '2025-04-27',
                    ],
                ],
            ],
            'two_terms_with_deadline_applicable_gets_the_last_date' => [
                '2025-04-28',
                [
                    [
                        'type' => TermType::NOTICE_OF_DEFAULT,
                        'end_date' => '2025-04-28',
                    ],
                    [
                        'type' => TermType::APPEAL_NOT_TIMELY,
                        'end_date' => '2025-04-28',
                    ],
                ],
            ],
        ];
    }

    public function testPredecessorThroughParent(): void
    {
        $collection = new PetitionTermCollection();
        $parentId = $this->faker()->uuid();
        $termToCheck = new PetitionTerm(['type' => TermType::PENALTY, 'parent_id' => $parentId]);

        $collection->push(
            new PetitionTerm(['id' => $parentId, 'type' => TermType::NOTICE_OF_DEFAULT]),
            $termToCheck,
        );

        $this->assertTrue($collection->hasPredecessor($termToCheck));
    }

    /**
     * @param array<string, mixed> $terms
     */
    #[DataProvider('checkOfPredecessorDataProvider')]
    public function testCheckOfPredecessor(array $terms, bool $expected): void
    {
        $collection = new PetitionTermCollection();

        foreach ($terms as $term) {
            $collection->push(new PetitionTerm(['type' => $term->value]));
        }

        $this->assertSame($expected, $collection->hasPredecessor($collection->last()));
    }

    // I want a data provider fot check of predecessor with a variable number of terms
    public static function checkOfPredecessorDataProvider(): iterable
    {
        return [
            'one_term' => [
                'terms' => [
                    TermType::FIRST,
                ],
                'expected' => false,
            ],
            'one_term_without_predecessor' => [
                'terms' => [
                    TermType::SECOND,
                ],
                'expected' => false,
            ],
            'two_terms' => [
                'terms' => [
                    TermType::FIRST,
                    TermType::SECOND,
                ],
                'expected' => true,
            ],
            'two_terms_committee_hearing' => [
                'terms' => [
                    TermType::FIRST,
                    TermType::COMMITTEE_HEARING,
                ],
                'expected' => true,
            ],

            'three_terms' => [
                'terms' => [
                    TermType::FIRST,
                    TermType::SECOND,
                    TermType::THIRD,
                ],
                'expected' => true,
            ],
        ];
    }

    public function testLastSuspendableInUnsortedOrder(): void
    {
        $collection = new PetitionTermCollection();
        $collection->push(
            new PetitionTerm(['type' => TermType::FIRST]),
            new PetitionTerm(['type' => TermType::THIRD]),
            new PetitionTerm(['type' => TermType::COMMITTEE_HEARING]),
            new PetitionTerm(['type' => TermType::SECOND]),
        );
        $this->assertEquals(TermType::THIRD, $collection->getLegalDateApplicableTerm()->type);
    }

    #[DataProvider('suspendableProvider')]
    public function testLastSuspendable(bool $expected, TermType $term): void
    {
        $collection = new PetitionTermCollection();
        $term = new PetitionTerm(['type' => $term->value]);
        $collection->push($term);
        $this->assertEquals($expected, $collection->isLastSuspendable($term));
    }

    public static function suspendableProvider(): iterable
    {
        return [
            'suspendable_term' => [
                'expected' => true,
                'term' => TermType::FIRST,
            ],
            'no_suspendable_term' => [
                'expected' => false,
                'term' => TermType::NOTICE_OF_DEFAULT,
            ],
        ];
    }

    public function testHasFirstTerm(): void
    {
        $collection = new PetitionTermCollection();
        $term = new PetitionTerm(['type' => TermType::FIRST]);
        $collection->push($term);
        $this->assertTrue($collection->hasFirstTerm());
        $this->assertEquals($term, $collection->getFirstTerm());
    }

    public function testSecondTerm(): void
    {
        $collection = new PetitionTermCollection();
        $term = new PetitionTerm(['type' => TermType::SECOND]);
        $collection->push($term);
        $this->assertTrue($collection->hasSecondTerm());
        $this->assertEquals($term, $collection->getSecondTerm());
    }

    public function testHasObjectionPeriod(): void
    {
        $collection = new PetitionTermCollection();
        $term = new PetitionTerm(['type' => TermType::OBJECTION_PERIOD]);
        $collection->push($term);
        $this->assertTrue($collection->hasObjectionPeriod());
        $this->assertEquals($term, $collection->getObjectionPeriod());
    }

    public function testHasCommitteeHearing(): void
    {
        $collection = new PetitionTermCollection();
        $term = new PetitionTerm(['type' => TermType::COMMITTEE_HEARING]);
        $collection->push($term);
        $this->assertTrue($collection->hasCommitteeHearing());
        $this->assertEquals($term, $collection->getCommitteeHearingTerm());
    }

    public function testTermParentAndChild(): void
    {
        $collection = new PetitionTermCollection();
        $parentTerm = PetitionTerm::factory()->create();

        $childTerm = PetitionTerm::factory()->create([
            'parent_id' => $parentTerm->id,
        ]);
        $collection->push($parentTerm, $childTerm);

        $this->assertEquals($collection->getChildTerm($parentTerm), $childTerm);
        $this->assertEquals($collection->getParentTerm($childTerm), $parentTerm);
    }

    /**
     * @param array<string, mixed> $term_types
     */
    #[DataProvider('predecessorProvider')]
    public function testTermOrNullOnTermTypePredecessor(array $term_types, TermType $term_type_to_check, ?TermType $expected): void
    {
        $collection = new PetitionTermCollection();
        foreach ($term_types as $termType) {
            $collection->push(new PetitionTerm(['type' => $termType->value]));
        }
        $this->assertEquals($expected, $collection->getPredecessorFromType($term_type_to_check)?->type);
    }

    /**
     * @return array<string, mixed>
     */
    public static function predecessorProvider(): array
    {
        return [
            'first_term' => [
                'term_types' => [],
                'term_type_to_check' => TermType::FIRST,
                'expected' => null,
            ],
            'second_term_without_predecessor' => [
                'term_types' => [],
                'term_type_to_check' => TermType::SECOND,
                'expected' => null,
            ],
            'second_term_with_predecessor' => [
                'term_types' => [
                    TermType::FIRST,
                ],
                'term_type_to_check' => TermType::SECOND,
                'expected' => TermType::FIRST,
            ],
            'second_term_with_committee_hearing_predecessor' => [
                'term_types' => [
                    TermType::COMMITTEE_HEARING,
                ],
                'term_type_to_check' => TermType::SECOND,
                'expected' => TermType::COMMITTEE_HEARING,
            ],
            'third_term_without_predecessor' => [
                'term_types' => [],
                'term_type_to_check' => TermType::THIRD,
                'expected' => null,
            ],
            'third_term_with_predecessor' => [
                'term_types' => [
                    TermType::SECOND,
                ],
                'term_type_to_check' => TermType::THIRD,
                'expected' => TermType::SECOND,
            ],
            'committee_hearing_term_with_predecessor' => [
                'term_types' => [
                    TermType::FIRST,
                ],
                'term_type_to_check' => TermType::COMMITTEE_HEARING,
                'expected' => TermType::FIRST,
            ],
            'committee_hearing_term_without_predecessor' => [
                'term_types' => [],
                'term_type_to_check' => TermType::COMMITTEE_HEARING,
                'expected' => null,
            ],
        ];
    }

    public function testLatestEndDateWithEmptyCollection(): void
    {
        $collection = new PetitionTermCollection();

        $this->assertNull($collection->latestEndDate());
    }

    public function testLatestEndDateWithSingleTerm(): void
    {
        $endDate = CalendarDate::createFromFormat('Y-m-d', '2024-05-15');
        $collection = new PetitionTermCollection();
        $collection->push(
            PetitionTerm::factory()->make([
                'type' => TermType::FIRST,
                'start_date' => '2024-05-01',
                'duration_in_days' => 15,
                'end_date' => $endDate,
            ]),
        );

        $this->assertTrue($endDate->equals($collection->latestEndDate()));
    }

    public function testLatestEndDateWithMultipleTerms(): void
    {
        $latestEndDate = CalendarDate::createFromFormat('Y-m-d', '2024-06-01');
        $collection = new PetitionTermCollection();

        $collection->push(
            PetitionTerm::factory()->make([
                'type' => TermType::FIRST,
                'start_date' => '2024-05-01',
                'duration_in_days' => 15,
                'end_date' => CalendarDate::createFromFormat('Y-m-d', '2024-05-15'),
            ]),
            PetitionTerm::factory()->make([
                'type' => TermType::SECOND,
                'start_date' => '2024-05-16',
                'duration_in_days' => 17,
                'end_date' => $latestEndDate,
            ]),
            PetitionTerm::factory()->make([
                'type' => TermType::PENALTY,
                'start_date' => '2024-05-10',
                'duration_in_days' => 5,
                'end_date' => CalendarDate::createFromFormat('Y-m-d', '2024-05-14'),
            ]),
        );

        $this->assertTrue($latestEndDate->equals($collection->latestEndDate()));
    }
}
