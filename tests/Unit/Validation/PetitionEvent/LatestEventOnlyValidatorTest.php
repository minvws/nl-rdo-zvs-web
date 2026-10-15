<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\PetitionEvent;

use App\Enums\PetitionEventType;
use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\Validation\PetitionEvent\PetitionEventRules;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function __;
use function collect;

/**
 * Event types whose only rule is that their date may not lie before the last event.
 */
class LatestEventOnlyValidatorTest extends TestCase
{
    #[Test]
    #[DataProvider('latestEventOnlyTypeProvider')]
    public function testItPassesOnOrAfterTheLastEvent(PetitionEventType $type): void
    {
        $state = $this->stateWithLastEventOn('2025-01-15');

        $this->assertTrue($this->validateOn($type, '2025-01-15', $state)->isValid());
        $this->assertTrue($this->validateOn($type, '2025-02-01', $state)->isValid());
    }

    #[Test]
    #[DataProvider('latestEventOnlyTypeProvider')]
    public function testItFailsBeforeTheLastEvent(PetitionEventType $type): void
    {
        $result = $this->validateOn($type, '2025-01-10', $this->stateWithLastEventOn('2025-01-15'));

        $this->assertSame(
            ['date' => __('term.validation.common.date_must_be_latest_event', ['event' => $type->label()])],
            $result->getErrors(),
        );
    }

    /**
     * @return array<string, array{PetitionEventType}>
     */
    public static function latestEventOnlyTypeProvider(): array
    {
        return [
            PetitionEventType::ACTUAL_DISCLOSURE->name => [PetitionEventType::ACTUAL_DISCLOSURE],
            PetitionEventType::PUBLICATION_DATE->name => [PetitionEventType::PUBLICATION_DATE],
            PetitionEventType::OPINION_OUTSIDE_TERM->name => [PetitionEventType::OPINION_OUTSIDE_TERM],
        ];
    }

    private function stateWithLastEventOn(string $date): DerivedState
    {
        return new DerivedState()->addEvents(collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create($date),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
        ]));
    }

    private function validateOn(PetitionEventType $type, string $date, DerivedState $state): ValidationResult
    {
        return PetitionEventRules::validate($type, new PetitionEventData(
            type: $type,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::now(),
        ), $state);
    }
}
