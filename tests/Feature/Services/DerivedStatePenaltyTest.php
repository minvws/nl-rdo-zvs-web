<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\Services\DerivedState;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PenaltyData;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\FakeLegalTermDeadlineCalculator;

use function collect;

/**
 * A petition with penalties from both a notice of default and an appeal decision not timely
 * - the decision period ends on Monday 2025-04-14
 * - the notice of default is received on 04-20; its term of 14 days runs from 04-21 to Sunday 05-04, which moves to
 *   Monday 05-05. Then 10 penalty days of €100 follow, from 05-06 to 05-15
 * - the appeal decision not timely is received on 05-20; its term of 30 days runs from 05-21 to Thursday 06-19, and is
 *   not moved to a working day. Then 5 penalty days of €50 follow, from 06-20 to 06-24.
 */
class DerivedStatePenaltyTest extends FeatureTestCase
{
    #[Test]
    public function testPenaltyPeriodsEndPerTerm(): void
    {
        $derivedState = $this->build();

        $this->assertSame('2025-05-15', $derivedState->penaltyPeriodEndDateForTerm(TermType::NOTICE_OF_DEFAULT)?->toDateString());
        $this->assertSame('2025-06-24', $derivedState->penaltyPeriodEndDateForTerm(TermType::APPEAL_NOT_TIMELY)?->toDateString());
        $this->assertNull($derivedState->penaltyPeriodEndDateForTerm(TermType::DECISION_PERIOD));
    }

    /**
     * @return array<string, array{string, TermType, int}>
     */
    public static function penaltyTodayProvider(): array
    {
        return [
            'a penalty day of the notice of default' => ['2025-05-10', TermType::NOTICE_OF_DEFAULT, 100],
            'a penalty day of the other term' => ['2025-05-10', TermType::APPEAL_NOT_TIMELY, 0],
            'a penalty day of the appeal decision not timely' => ['2025-06-21', TermType::APPEAL_NOT_TIMELY, 50],
            'a day of the notice of default term itself' => ['2025-05-01', TermType::NOTICE_OF_DEFAULT, 0],
            'a day of the decision period' => ['2025-04-01', TermType::NOTICE_OF_DEFAULT, 0],
            'a day after every penalty' => ['2025-06-25', TermType::APPEAL_NOT_TIMELY, 0],
            'a day before the petition' => ['2024-12-01', TermType::NOTICE_OF_DEFAULT, 0],
        ];
    }

    #[Test]
    #[DataProvider('penaltyTodayProvider')]
    public function testPenaltyToday(string $date, TermType $term, int $expected): void
    {
        $this->assertSame($expected, $this->build()->penaltyTodayForTerm(CalendarDate::create($date), $term));
    }

    /**
     * Once the deadline of a term has passed, the end of its penalties counts as a deadline too, but only when nothing
     * else ends later
     *
     * @return array<string, array{string, string}>
     */
    public static function deadlineProvider(): array
    {
        return [
            'before the notice of default deadline' => ['2025-05-01', '2025-06-19'],
            'penalties of the notice of default running, a later term still open' => ['2025-06-01', '2025-06-19'],
            'penalties of the appeal decision not timely running' => ['2025-06-21', '2025-06-24'],
            'after every penalty' => ['2025-07-01', '2025-06-24'],
        ];
    }

    #[Test]
    #[DataProvider('deadlineProvider')]
    public function testDeadlineIsTheLastDayAnythingRuns(string $today, string $expected): void
    {
        $this->travelTo(CarbonImmutable::parse($today));

        $this->assertSame($expected, $this->build()->deadlineDate()?->toDateString());
    }

    private function build(): DerivedState
    {
        FakeLegalTermDeadlineCalculator::bind($this->app);

        return new DerivedState()->addEvents(collect([
            $this->event(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 42),
            $this->event(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-03-03', 42),
            $this->event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, '2025-04-20', 14, [new PenaltyData(amount: 100, duration: 10)]),
            $this->event(PetitionEventType::APPEAL_DECISION_NOT_TIMELY, '2025-05-20', 30, [new PenaltyData(amount: 50, duration: 5)]),
        ]))->buildCalendar();
    }

    /**
     * @param array<int, PenaltyData> $penalties
     */
    private function event(PetitionEventType $type, string $date, int $duration, array $penalties = []): PetitionEventData
    {
        return new PetitionEventData(
            type: $type,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::parse($date),
            duration: $duration,
            penalties: $penalties,
        );
    }
}
