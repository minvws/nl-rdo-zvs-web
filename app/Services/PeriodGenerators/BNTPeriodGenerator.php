<?php

declare(strict_types=1);

namespace App\Services\PeriodGenerators;

use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\PenaltyData;
use App\ValueObjects\PetitionEventData;
use Illuminate\Support\Collection;

class BNTPeriodGenerator implements PeriodGeneratorInterface
{
    /**
     * @param Collection<int, PetitionEventData> $events
     */
    public function generate(Collection $events, EventCalendar $calendar): void
    {
        $bntEvents = $events->filter(
            static fn(PetitionEventData $petitionEventData): bool => $petitionEventData->type === PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
        );

        foreach ($bntEvents as $bntEvent) {
            $this->processBNTEvent($bntEvent, $calendar);
        }
    }

    private function processBNTEvent(PetitionEventData $event, EventCalendar $calendar): void
    {
        $startDate = $event->date->addDay();
        $budget = $event->duration ?? 0;

        if ($budget === 0) {
            return;
        }

        $this->addBNTBudgetDays($calendar, $startDate, $budget);

        if ($event->penalties !== []) {
            $this->addPenaltyPeriods($calendar, $startDate, $budget, $event->penalties);
        }
    }

    private function addBNTBudgetDays(
        EventCalendar $calendar,
        CalendarDate $startDate,
        int $budget,
    ): void {
        for ($i = 0; $i < $budget; $i++) {
            $currentDate = $startDate->addDays($i);
            $isFirst = $i === 0;
            $isLast = $i === $budget - 1;

            $calendar->upsertDay($currentDate, [
                'applicableTerm' => TermType::APPEAL_NOT_TIMELY->value,
                'isBudgetDay' => true,
                'isFirstDayOfBudget' => $isFirst,
                'isLastDayOfBudget' => $isLast,
                'isDeadline' => $isLast,
            ]);
        }
    }

    /**
     * @param array<int, PenaltyData> $penalties
     */
    private function addPenaltyPeriods(
        EventCalendar $calendar,
        CalendarDate $bntStartDate,
        int $bntBudget,
        array $penalties,
    ): void {
        $currentDate = $bntStartDate->addDays($bntBudget);

        foreach ($penalties as $penalty) {
            for ($i = 0; $i < $penalty->duration; $i++) {
                $calendar->upsertDay($currentDate, [
                    'applicableTerm' => TermType::PENALTY_PERIOD->value,
                    'penaltyTodayInEuros' => $penalty->amount,
                    'isBudgetDay' => false,
                    'isFirstDayOfBudget' => false,
                    'isLastDayOfBudget' => false,
                ]);
                $currentDate = $currentDate->addDay();
            }
        }
    }
}
