<?php

declare(strict_types=1);

namespace App\Services\PeriodGenerators;

use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\Facades\LegalTermDeadlineCalculator;
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
        $withdrawalCount = $events->filter(
            static fn(PetitionEventData $event): bool => $event->type === PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN,
        )->count();

        $activeIGSEvents = $events->filter(
            static fn(PetitionEventData $event): bool => $event->type === PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
        )->sortBy(
            static fn(PetitionEventData $event): string => $event->createdAt->toDateTimeString(),
        )->skip($withdrawalCount)->values();

        foreach ($activeIGSEvents as $igsEvent) {
            $this->processIGSEvent($igsEvent, $calendar);
        }
    }

    private function processIGSEvent(PetitionEventData $event, EventCalendar $calendar): void
    {
        $decisionDeadline = $this->findDecisionPeriodDeadline($calendar);
        if ($decisionDeadline instanceof CalendarDate && $event->date->lessThanOrEqualTo($decisionDeadline)) {
            return; // IGS valt binnen beslistermijn → skippen
        }

        $startDate = $event->date->addDay();
        $budget = $event->duration ?? 0;

        if ($budget === 0) {
            return;
        }

        $actualDeadline = $this->addIGSBudgetDays($calendar, $startDate, $budget);

        if ($event->penalties !== []) {
            $this->addPenaltyPeriods($calendar, $actualDeadline, $event->penalties);
        }
    }

    private function addIGSBudgetDays(
        EventCalendar $calendar,
        CalendarDate $startDate,
        int $budget,
    ): CalendarDate {
        for ($i = 0; $i < $budget; $i++) {
            $currentDate = $startDate->addDays($i);
            $isFirst = $i === 0;
            $isLast = $i === $budget - 1;

            $calendar->upsertDay($currentDate, [
                'applicableTerm' => TermType::NOTICE_OF_DEFAULT->value,
                'isBudgetDay' => true,
                'isFirstDayOfBudget' => $isFirst,
                'isLastDayOfBudget' => $isLast,
            ]);
        }

        $proposedDeadline = $startDate->addDays($budget - 1);
        return $this->markDeadline($calendar, $proposedDeadline);
    }

    /**
     * @param array<int, PenaltyData> $penalties
     */
    private function addPenaltyPeriods(
        EventCalendar $calendar,
        CalendarDate $penaltyStartDate,
        array $penalties,
    ): void {
        $currentDate = $penaltyStartDate->addDay();

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

    private function findDecisionPeriodDeadline(EventCalendar $calendar): ?CalendarDate
    {
        return $calendar
            ->filter(
                static fn($day): bool => $day->applicableTerm === TermType::DECISION_PERIOD->value
                    && $day->isDeadline,
            )
            ->first()
            ?->date;
    }

    private function markDeadline(EventCalendar $calendar, CalendarDate $proposedDeadline): CalendarDate // ← returns actual
    {
        $actualDeadline = LegalTermDeadlineCalculator::calculate($proposedDeadline);

        if ($proposedDeadline->equals($actualDeadline)) {
            $calendar->upsertDay($proposedDeadline, ['isDeadline' => true]);
            return $proposedDeadline; // ← no ATW shift, deadline is the proposed date
        }

        $this->addAtwDaysIfDeadlineMoved($calendar, $proposedDeadline, $actualDeadline);
        return $actualDeadline; // ← shifted deadline
    }

    private function addAtwDaysIfDeadlineMoved(
        EventCalendar $calendar,
        CalendarDate $proposedDeadline,
        CalendarDate $actualDeadline,
    ): void {
        $calendar->upsertDay($proposedDeadline, [
            'applicableTerm' => TermType::NOTICE_OF_DEFAULT->value,
            'isATW' => true,
            'isFirstDayOfBudget' => false,
        ]);

        $currentDate = $proposedDeadline->addDay();
        while ($currentDate->isBefore($actualDeadline)) {
            $calendar->upsertDay($currentDate, [
                'applicableTerm' => TermType::NOTICE_OF_DEFAULT->value,
                'isBudgetDay' => false,
                'isATW' => true,
                'isFirstDayOfBudget' => false,
                'isLastDayOfBudget' => false,
            ]);
            $currentDate = $currentDate->addDay();
        }

        $calendar->upsertDay($actualDeadline, [
            'applicableTerm' => TermType::NOTICE_OF_DEFAULT->value,
            'isBudgetDay' => false,
            'isATW' => false,
            'isLastDayOfBudget' => true,
            'isDeadline' => true,
            'isFirstDayOfBudget' => false,
        ]);
    }
}
