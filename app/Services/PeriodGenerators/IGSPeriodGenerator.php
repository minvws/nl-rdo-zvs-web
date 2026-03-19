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

class IGSPeriodGenerator implements PeriodGeneratorInterface
{
    /**
     * @param Collection<int, PetitionEventData> $events
     */
    public function generate(Collection $events, EventCalendar $calendar): void
    {
        $igsEvents = $events->filter(
            static fn(PetitionEventData $petitionEventData): bool => $petitionEventData->type === PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
        );

        foreach ($igsEvents as $igsEvent) {
            $this->processIGSEvent($igsEvent, $calendar);
        }
    }

    private function processIGSEvent(PetitionEventData $event, EventCalendar $calendar): void
    {
        $startDate = $event->date->addDay();
        $budget = $event->duration ?? 0;

        if ($budget === 0) {
            return;
        }

        $this->addIGSBudgetDays($calendar, $startDate, $budget);

        if ($event->penalties !== []) {
            $this->addPenaltyPeriods($calendar, $startDate, $budget, $event->penalties);
        }
    }

    private function addIGSBudgetDays(
        EventCalendar $calendar,
        CalendarDate $startDate,
        int $budget,
    ): void {
        for ($i = 0; $i < $budget; $i++) {
            $currentDate = $startDate->addDays($i);
            $isFirst = $i === 0;
            $isLast = $i === $budget - 1;

            $calendar->upsertDay($currentDate, [
                'applicableTerm' => TermType::NOTICE_OF_DEFAULT->value,
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
        CalendarDate $igsStartDate,
        int $igsBudget,
        array $penalties,
    ): void {
        $currentDate = $igsStartDate->addDays($igsBudget);

        foreach ($penalties as $penalty) {
            for ($i = 0; $i < $penalty->duration; $i++) {
                $calendar->upsertDay($currentDate, [
                    'applicableTerm' => TermType::PENALTY_PERIOD->value,
                    'penaltyTodayInEuros' => $penalty->amount,
                    'penaltySourceTerm' => TermType::NOTICE_OF_DEFAULT->value,
                    'isBudgetDay' => false,
                    'isFirstDayOfBudget' => false,
                    'isLastDayOfBudget' => false,
                ]);
                $currentDate = $currentDate->addDay();
            }
        }
    }
}
