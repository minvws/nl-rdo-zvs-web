<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\Rules;

use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\Services\DerivedState;
use App\Support\Str\TermsString;
use App\Validation\Rules\NextDayMustBeInTermRule;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Helpers\FakeLegalTermDeadlineCalculator;
use Tests\TestCase;

use function __;
use function collect;

class NextDayMustBeInTermRuleTest extends TestCase
{
    /**
     * The objection period runs from 2025-01-02 to Wednesday 02-12, the decision period from 02-13 to Wednesday 03-26
     *
     * @return array<string, array{string, bool}>
     */
    public static function nextDayProvider(): array
    {
        return [
            'the day before the objection period' => ['2025-01-01', true],
            'the day before the primary decision' => ['2024-12-31', false],
            'inside the objection period' => ['2025-01-10', true],
            'the last day of the objection period' => ['2025-02-12', true],
            'the day before the last day of the decision period' => ['2025-03-25', true],
            'the last day of the decision period' => ['2025-03-26', false],
        ];
    }

    #[Test]
    #[DataProvider('nextDayProvider')]
    public function testNextDayMustBeInTerm(string $date, bool $passes): void
    {
        FakeLegalTermDeadlineCalculator::bind($this->app);
        $allowedTerms = [TermType::OBJECTION_PERIOD, TermType::DECISION_PERIOD];

        // The rule builds the calendar itself
        $state = new DerivedState()->addEvents(collect([
            $this->event(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 42),
            $this->event(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-20', 42),
        ]));

        $result = new NextDayMustBeInTermRule($allowedTerms)->validate(
            $this->event(PetitionEventType::LETTER_OF_SUSPENSION_SENT, $date, 14),
            $state,
        );

        if ($passes) {
            $this->assertNull($result);

            return;
        }

        $this->assertSame(
            __('term.validation.common.date_must_be_in_term', [
                'event' => PetitionEventType::LETTER_OF_SUSPENSION_SENT->label(),
                'term' => TermsString::format($allowedTerms),
            ]),
            $result?->getErrors()['date'],
        );
    }

    private function event(PetitionEventType $type, string $date, int $duration): PetitionEventData
    {
        return new PetitionEventData(
            type: $type,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::parse($date),
            duration: $duration,
        );
    }
}
