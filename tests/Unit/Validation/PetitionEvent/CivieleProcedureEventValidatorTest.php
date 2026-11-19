<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\PetitionEvent;

use App\Enums\PetitionEventType;
use App\Services\DerivedState;
use App\Validation\PetitionEvent\PetitionEventRules;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

use function __;

class CivieleProcedureEventValidatorTest extends TestCase
{
    private int $createdSeconds = 0;

    /**
     * @return array<string, array{PetitionEventType}>
     */
    public static function civieleProcedureEventTypeProvider(): array
    {
        return [
            'court session' => [PetitionEventType::COURT_SESSION],
            'statement of defence' => [PetitionEventType::STATEMENT_OF_DEFENCE],
        ];
    }

    #[DataProvider('civieleProcedureEventTypeProvider')]
    public function testDateBeforeTheReceiptOfCivilProceedingIsRejected(PetitionEventType $type): void
    {
        $result = PetitionEventRules::validate(
            $type,
            $this->event($type, '2025-01-09'),
            $this->state(),
        );

        $this->assertFalse($result->isValid());
        $this->assertSame(
            __('term.validation.common.date_must_be_on_or_after_dependency', [
                'event' => $type->label(),
                'dependency' => PetitionEventType::RECEIPT_OF_CIVIL_PROCEEDING->label(),
            ]),
            $result->getErrors()['date'],
        );
    }

    #[DataProvider('civieleProcedureEventTypeProvider')]
    public function testDateOnOrAfterTheReceiptOfCivilProceedingIsAccepted(PetitionEventType $type): void
    {
        $state = $this->state();

        $this->assertTrue(
            PetitionEventRules::validate($type, $this->event($type, '2025-01-10'), $state)->isValid(),
        );
        $this->assertTrue(
            PetitionEventRules::validate($type, $this->event($type, '2025-03-01'), $state)->isValid(),
        );
    }

    #[DataProvider('civieleProcedureEventTypeProvider')]
    public function testEventsMayOccurRepeatedlyInAnyOrder(PetitionEventType $type): void
    {
        $state = $this->state(
            $this->event(PetitionEventType::STATEMENT_OF_DEFENCE, '2025-03-01'),
            $this->event(PetitionEventType::COURT_SESSION, '2025-02-01'),
            $this->event($type, '2025-04-01'),
        );

        $this->assertTrue(
            PetitionEventRules::validate($type, $this->event($type, '2025-01-15'), $state)->isValid(),
        );
    }

    #[DataProvider('civieleProcedureEventTypeProvider')]
    public function testEventRequiresTheReceiptOfCivilProceeding(PetitionEventType $type): void
    {
        $state = new DerivedState()->addEvents(new Collection());

        $this->assertFalse(
            PetitionEventRules::validate($type, $this->event($type, '2025-02-01'), $state)->isValid(),
        );
    }

    public function testReceiptOfCivilProceedingMayOnlyBeAddedOnce(): void
    {
        $type = PetitionEventType::RECEIPT_OF_CIVIL_PROCEEDING;

        $this->assertTrue(PetitionEventRules::validate(
            $type,
            $this->event($type, '2025-01-10'),
            new DerivedState()->addEvents(new Collection()),
        )->isValid());
        $this->assertFalse(
            PetitionEventRules::validate(
                $type,
                $this->event($type, '2025-01-11'),
                $this->state(),
            )->isValid(),
        );
    }

    public function testFinalResultMustBeTheLatestEventAndOnlyOnce(): void
    {
        $type = PetitionEventType::FINAL_RESULT;
        $state = $this->state($this->event(PetitionEventType::COURT_SESSION, '2025-02-01'));

        $this->assertTrue(
            PetitionEventRules::validate($type, $this->event($type, '2025-03-01'), $state)->isValid(),
        );
        $this->assertFalse(
            PetitionEventRules::validate($type, $this->event($type, '2025-01-20'), $state)->isValid(),
        );

        $withResult = $this->state($this->event($type, '2025-03-01'));
        $this->assertFalse(
            PetitionEventRules::validate(
                $type,
                $this->event($type, '2025-04-01'),
                $withResult,
            )->isValid(),
        );
    }

    private function state(PetitionEventData ...$events): DerivedState
    {
        return new DerivedState()->addEvents(new Collection([
            $this->event(PetitionEventType::RECEIPT_OF_CIVIL_PROCEEDING, '2025-01-10', -1),
            ...$events,
        ]));
    }

    /**
     * Events are created in the order they were added, which is what the "latest event" rule goes by.
     */
    private function event(PetitionEventType $type, string $date, ?int $createdSeconds = null): PetitionEventData
    {
        if ($createdSeconds === null) {
            $this->createdSeconds++;
            $createdSeconds = $this->createdSeconds;
        }

        return new PetitionEventData(
            type: $type,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::now()->addSeconds($createdSeconds),
        );
    }
}
