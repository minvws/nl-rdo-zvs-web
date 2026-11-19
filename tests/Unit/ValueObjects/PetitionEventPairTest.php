<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects;

use App\Enums\AdjournmentEndReason;
use App\Enums\PetitionEventType;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\PetitionEventPair;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function collect;

class PetitionEventPairTest extends TestCase
{
    #[Test]
    public function aSuspensionIsEndedByTheFirstEndAfterTheLetter(): void
    {
        $pairs = PetitionEventPair::suspensions(collect([
            $this->event(PetitionEventType::SUSPENSION_END, '2026-01-20'),
            $this->event(PetitionEventType::LETTER_OF_SUSPENSION_SENT, '2026-01-10'),
            $this->event(PetitionEventType::SUSPENSION_END, '2026-01-10'),
            $this->event(PetitionEventType::LETTER_OF_SUSPENSION_SENT, '2026-02-01'),
        ]));

        $this->assertCount(2, $pairs);
        $this->assertSame('2026-01-10', $pairs[0]->start->date->toDateString());
        $this->assertSame('2026-01-20', $pairs[0]->end?->date->toDateString());
        $this->assertSame('2026-02-01', $pairs[1]->start->date->toDateString());
        $this->assertNull($pairs[1]->end);
    }

    #[Test]
    public function unspecifiedAdjournmentsArePairedInChronologicalOrder(): void
    {
        $pairs = PetitionEventPair::unspecifiedAdjournments(collect([
            $this->event(PetitionEventType::UNSPECIFIED_ADJOURNMENT, '2026-03-01'),
            $this->event(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END, '2026-02-01'),
            $this->event(PetitionEventType::UNSPECIFIED_ADJOURNMENT, '2026-01-01'),
        ]));

        $this->assertCount(2, $pairs);
        $this->assertSame('2026-01-01', $pairs[0]->start->date->toDateString());
        $this->assertSame('2026-02-01', $pairs[0]->end?->date->toDateString());
        $this->assertSame('2026-03-01', $pairs[1]->start->date->toDateString());
        $this->assertNull($pairs[1]->end);
    }

    private function event(PetitionEventType $type, string $date): PetitionEventData
    {
        return new PetitionEventData(
            type: $type,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::now(),
            reasoning: $type->requiresReasoning() ? AdjournmentEndReason::Event->value : null,
        );
    }
}
