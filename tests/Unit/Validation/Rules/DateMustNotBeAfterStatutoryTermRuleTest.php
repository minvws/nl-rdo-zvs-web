<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\Rules;

use App\Enums\AdjournmentEndReason;
use App\Enums\PetitionEventType;
use App\Enums\UnspecifiedAdjournmentMode;
use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\Validation\Rules\DateMustNotBeAfterStatutoryTermRule;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function __;
use function collect;

class DateMustNotBeAfterStatutoryTermRuleTest extends TestCase
{
    /**
     * The statutory term runs from 2026-04-02 to Thursday 2026-04-30.
     *
     * @return array<string, array{string, bool}>
     */
    public static function statutoryTermProvider(): array
    {
        return [
            'inside the statutory term' => ['2026-04-15', true],
            'on its last day' => ['2026-04-30', true],
            'the day after its last day' => ['2026-05-01', false],
        ];
    }

    #[Test]
    #[DataProvider('statutoryTermProvider')]
    public function testOnlyPassesUpToTheLastStatutoryDay(string $date, bool $passes): void
    {
        $result = $this->validateOn($date, $this->state([
            $this->event(PetitionEventType::RECEIPT_OF_OBJECTION, '2026-04-01', 29),
        ]));

        $this->assertSame($passes, $result === null);
    }

    #[Test]
    #[DataProvider('statutoryTermProvider')]
    public function testUsesTheOriginalLastDayWhenAnAdjournmentReplacedIt(string $date, bool $passes): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-05-10'));

        // The new term replaces the statutory days from 04-21; the original last day stays labelled.
        $result = $this->validateOn($date, $this->state([
            $this->event(PetitionEventType::RECEIPT_OF_OBJECTION, '2026-04-01', 29),
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                date: CalendarDate::create('2026-04-01'),
                createdAt: CarbonImmutable::now(),
                duration: 14,
                withdrawalDuration: 28,
            ),
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
                date: CalendarDate::create('2026-04-21'),
                createdAt: CarbonImmutable::now(),
                reasoning: AdjournmentEndReason::Event->value,
            ),
        ]));

        $this->assertSame($passes, $result === null);
    }

    #[Test]
    public function testPassesWithoutAStatutoryTerm(): void
    {
        $this->assertNull($this->validateOn('2026-05-01', $this->state([])));
    }

    #[Test]
    public function testExplainsWhyTheDateIsRejected(): void
    {
        $result = $this->validateOn('2026-05-01', $this->state([
            $this->event(PetitionEventType::RECEIPT_OF_OBJECTION, '2026-04-01', 29),
        ]));

        $this->assertSame(
            ['date' => __('term.validation.common.date_after_statutory_term', ['event' => PetitionEventType::ADJOURNMENT->label()])],
            $result?->getErrors(),
        );
    }

    /**
     * @param list<PetitionEventData> $events
     */
    private function state(array $events): DerivedState
    {
        return new DerivedState()
            ->withUnspecifiedAdjournmentMode(UnspecifiedAdjournmentMode::CONTINUES_TERMS)
            ->addEvents(collect($events));
    }

    private function validateOn(string $date, DerivedState $state): ?ValidationResult
    {
        return new DateMustNotBeAfterStatutoryTermRule()->validate(
            $this->event(PetitionEventType::ADJOURNMENT, $date, 42),
            $state,
        );
    }

    private function event(PetitionEventType $type, string $date, int $duration): PetitionEventData
    {
        return new PetitionEventData(
            type: $type,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::now(),
            duration: $duration,
        );
    }
}
