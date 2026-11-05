<?php

declare(strict_types=1);

namespace Tests\Unit\Services\PetitionEvent;

use App\Enums\HearingForm;
use App\Enums\PetitionEventType;
use App\Enums\PetitionVariant;
use App\Enums\SuspensionType;
use App\Enums\UnspecifiedAdjournmentMode;
use App\Services\PetitionEvent\PetitionEventDeletionService;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\WizardEventCollection;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PetitionEventDeletionServiceTest extends TestCase
{
    private PetitionEventDeletionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(PetitionEventDeletionService::class);
    }

    #[Test]
    public function itAllowsDeletingEventsThatLaterEventsDoNotDependOn(): void
    {
        $events = $this->events([
            [PetitionEventType::PRIMARY_DECISION, '2025-01-01', 42],
            [PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-20', 42],
            [PetitionEventType::LETTER_OF_SUSPENSION_SENT, '2025-02-01', 14],
            [PetitionEventType::SUSPENSION_END, '2025-02-10', null],
            [PetitionEventType::HEARING_DATE, '2025-02-20', null],
        ]);

        $this->assertSame([false, false, false, true, true], $this->deletable($events));
    }

    #[Test]
    public function itAllowsDeletingAHearingBetweenOtherEvents(): void
    {
        $events = $this->events([
            [PetitionEventType::PRIMARY_DECISION, '2025-01-01', 42],
            [PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-20', 42],
            [PetitionEventType::HEARING_DATE, '2025-01-25', null],
            [PetitionEventType::LETTER_OF_SUSPENSION_SENT, '2025-02-01', 14],
            [PetitionEventType::SUSPENSION_END, '2025-02-10', null],
        ]);

        $this->assertTrue($this->canDelete($events, 2));
    }

    #[Test]
    public function itAllowsDeletingAnAdjournmentWhenLaterEventsStayWithinTheTerm(): void
    {
        $events = $this->events([
            [PetitionEventType::PRIMARY_DECISION, '2025-01-01', 42],
            [PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-20', 42],
            [PetitionEventType::ADJOURNMENT, '2025-02-01', 42],
            [PetitionEventType::LETTER_OF_SUSPENSION_SENT, '2025-02-15', 14],
            [PetitionEventType::SUSPENSION_END, '2025-02-20', null],
        ]);

        $this->assertTrue($this->canDelete($events, 2));
    }

    #[Test]
    public function itBlocksDeletingAnAdjournmentWhenALaterEventFallsOutsideTheShorterTerm(): void
    {
        $events = $this->events([
            [PetitionEventType::PRIMARY_DECISION, '2025-01-01', 42],
            [PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-20', 42],
            [PetitionEventType::ADJOURNMENT, '2025-02-01', 42],
            [PetitionEventType::LETTER_OF_SUSPENSION_SENT, '2025-04-10', 14],
        ]);

        $this->assertFalse($this->canDelete($events, 2));
    }

    #[Test]
    public function itBlocksDeletingWhenTheRemainingEventsCouldNotHaveBeenAdded(): void
    {
        // The second hearing is dated before the hearing added before it, which the rules do not allow.
        $events = $this->events([
            [PetitionEventType::PRIMARY_DECISION, '2025-01-01', 42],
            [PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-20', 42],
            [PetitionEventType::HEARING_DATE, '2025-02-15', null],
            [PetitionEventType::HEARING_DATE, '2025-02-01', null],
            [PetitionEventType::HEARING_DATE, '2025-02-20', null],
        ]);

        $this->assertSame([false, false, true, true, false], $this->deletable($events));
    }

    /**
     * @param array<int, array{PetitionEventType, string, int|null}> $definitions
     */
    private function events(array $definitions): WizardEventCollection
    {
        $events = WizardEventCollection::make();
        $createdAt = CarbonImmutable::parse('2025-06-01 12:00:00');

        foreach ($definitions as $position => [$type, $date, $duration]) {
            $events = $events->add(new PetitionEventData(
                type: $type,
                date: CalendarDate::create($date),
                createdAt: $createdAt->addMinutes($position),
                duration: $duration,
                suspensionType: $type->hasSuspensionType() ? SuspensionType::SUSPENSION : null,
                hearingForm: $type->hasHearingForm() ? HearingForm::PHYSICAL : null,
            ));
        }

        return $events;
    }

    private function canDelete(WizardEventCollection $events, int $index): bool
    {
        $event = $events->all()->get($index);
        $this->assertInstanceOf(PetitionEventData::class, $event);

        return $this->service
            ->deletableEvents($events, PetitionVariant::BEZWAAR, UnspecifiedAdjournmentMode::FREEZES_TERMS)
            ->contains($event);
    }

    /**
     * @return array<int, bool>
     */
    private function deletable(WizardEventCollection $events): array
    {
        return $events->all()
            ->keys()
            ->map(fn(int $index): bool => $this->canDelete($events, $index))
            ->all();
    }
}
