<?php

declare(strict_types=1);

namespace Tests\Unit\Services\PetitionEvent;

use App\Enums\AdjournmentEndReason;
use App\Enums\PetitionEventType;
use App\Enums\UnspecifiedAdjournmentMode;
use App\Services\PetitionEvent\PetitionEventTermService;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\WizardEventCollection;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PetitionEventTermServiceTest extends TestCase
{
    /**
     * @return array<string, array{PetitionEventType, int|null, int|null, string}>
     */
    public static function termTextProvider(): array
    {
        return [
            'a term in days' => [PetitionEventType::ADJOURNMENT, 42, null, '42 dagen'],
            'a single day' => [PetitionEventType::ADJOURNMENT, 1, null, '1 dag'],
            'no term' => [PetitionEventType::HEARING_DATE, null, null, ''],
            'zero days' => [PetitionEventType::ADJOURNMENT, 0, null, ''],
            'adjournment with a term for both endings' => [PetitionEventType::UNSPECIFIED_ADJOURNMENT, 14, 7, '14 / 7 dagen'],
            'adjournment without a term' => [PetitionEventType::UNSPECIFIED_ADJOURNMENT, null, null, ''],
        ];
    }

    #[Test]
    #[DataProvider('termTextProvider')]
    public function testTermText(
        PetitionEventType $type,
        ?int $duration,
        ?int $withdrawalDuration,
        string $expected,
    ): void {
        $events = WizardEventCollection::make()->add(new PetitionEventData(
            type: $type,
            date: CalendarDate::create('2026-04-01'),
            createdAt: CarbonImmutable::now(),
            duration: $duration,
            withdrawalDuration: $withdrawalDuration,
        ));

        $this->assertSame(
            [$expected],
            new PetitionEventTermService()->termTexts($events, UnspecifiedAdjournmentMode::CONTINUES_TERMS),
        );
    }

    /**
     * The original term ends on 2026-04-30.
     *
     * @return array<string, array{string, AdjournmentEndReason, string}>
     */
    public static function adjournmentEndProvider(): array
    {
        return [
            'event term runs past the original term' => ['2026-04-21', AdjournmentEndReason::Event, '14 dagen'],
            'withdrawal term runs past the original term' => ['2026-04-07', AdjournmentEndReason::Withdrawal, '28 dagen'],
            'original term runs longer' => ['2026-04-07', AdjournmentEndReason::Event, ''],
        ];
    }

    #[Test]
    #[DataProvider('adjournmentEndProvider')]
    public function testAdjournmentEndShowsTheTermItApplied(
        string $endDate,
        AdjournmentEndReason $reason,
        string $expected,
    ): void {
        $this->travelTo(CarbonImmutable::parse('2026-04-25'));

        $events = WizardEventCollection::make()
            ->add($this->event(PetitionEventType::RECEIPT_OF_OBJECTION, '2026-04-01', duration: 29))
            ->add($this->event(PetitionEventType::UNSPECIFIED_ADJOURNMENT, '2026-04-01', duration: 14, withdrawalDuration: 28))
            ->add($this->event(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END, $endDate, reasoning: $reason->value));

        $termTexts = new PetitionEventTermService()->termTexts($events, UnspecifiedAdjournmentMode::CONTINUES_TERMS);

        $this->assertSame(['29 dagen', '14 / 28 dagen', $expected], $termTexts);
    }

    private function event(
        PetitionEventType $type,
        string $date,
        ?int $duration = null,
        ?int $withdrawalDuration = null,
        ?string $reasoning = null,
    ): PetitionEventData {
        return new PetitionEventData(
            type: $type,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::now(),
            duration: $duration,
            reasoning: $reasoning,
            withdrawalDuration: $withdrawalDuration,
        );
    }
}
