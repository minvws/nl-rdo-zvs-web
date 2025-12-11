<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Terms;

use App\Enums\TermType;
use App\Models\PetitionTerm;
use App\Services\Terms\DeadlineCalculator;
use App\ValueObjects\CalendarDate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

use function collect;

class PetitionDeadlineCalculatorTest extends FeatureTestCase
{
    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('collections')]
    public function testDeadline(string $expected, array $data): void
    {
        $duration = 28;
        $collection = collect();
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

        $deadlineCalculator = new DeadlineCalculator();

        $deadline = $deadlineCalculator->calculateDeadline($collection);

        $this->assertTrue($deadline->equals($expected));
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
}
