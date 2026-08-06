<?php

declare(strict_types=1);

namespace App\Services\PeriodGenerators;

use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\Facades\LegalTermDeadlineCalculator;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PetitionEventData;
use Illuminate\Support\Collection;

class ObjectionPeriodGenerator implements PeriodGeneratorInterface
{
    private int $daysAdded = 0;
    private bool $firstDaySet = false;

    /**
     * @param Collection<int, PetitionEventData> $events
     */
    public function generate(Collection $events, EventCalendar $calendar): void
    {
        $primaryDecision = $events->first(
            static fn(PetitionEventData $petitionEventData): bool => $petitionEventData->type === PetitionEventType::PRIMARY_DECISION,
        );

        if (!$primaryDecision) {
            return;
        }

        $startDate = $primaryDecision->date->addDay();
        $budget = $primaryDecision->duration ?? 0;

        $this->addBudgetDaysExcludingFrozen($calendar, $startDate, $budget);
    }

    private function addBudgetDaysExcludingFrozen(
        EventCalendar $calendar,
        CalendarDate $startDate,
        int $budget,
    ): void {
        $this->daysAdded = 0;
        $this->firstDaySet = false;
        $currentDate = $startDate;

        while ($this->daysAdded < $budget) {
            $this->processDayForBudget($calendar, $currentDate, $budget);
            $currentDate = $currentDate->addDay();
        }

        $this->markDeadline($calendar, $currentDate->subDay());
    }

    private function processDayForBudget(EventCalendar $calendar, CalendarDate $currentDate, int $budget): void
    {
        // A day frozen by an unspecified adjournment does not spend objection-period budget,
        // shifting the term (and its deadline) forward, but stays part of the objection period.
        if ($this->isDayFrozen($calendar, $currentDate)) {
            $calendar->upsertDay($currentDate, [
                'applicableTerm' => TermType::OBJECTION_PERIOD->value,
                'isBudgetDay' => false,
                'isFirstDayOfBudget' => false,
                'isLastDayOfBudget' => false,
            ]);

            return;
        }

        $isFirst = !$this->firstDaySet;
        $isLast = $this->daysAdded === $budget - 1;

        $calendar->upsertDay($currentDate, [
            'applicableTerm' => TermType::OBJECTION_PERIOD->value,
            'isBudgetDay' => true,
            'isFirstDayOfBudget' => $isFirst,
            'isLastDayOfBudget' => $isLast,
        ]);

        $this->daysAdded++;
        $this->firstDaySet = true;
    }

    private function markDeadline(EventCalendar $calendar, CalendarDate $proposedDeadline): void
    {
        $actualDeadline = LegalTermDeadlineCalculator::calculate($proposedDeadline);

        if ($proposedDeadline->equals($actualDeadline)) {
            $calendar->upsertDay($proposedDeadline, [
                'isDeadline' => true,
            ]);

            return;
        }

        $this->addAtwDaysIfDeadlineMoved($calendar, $proposedDeadline, $actualDeadline);
    }

    private function isDayFrozen(EventCalendar $calendar, CalendarDate $date): bool
    {
        $existingDay = $calendar->findDay($date);

        return $existingDay instanceof EventCalendarDay && $existingDay->isUnspecifiedAdjournment;
    }

    private function addAtwDaysIfDeadlineMoved(
        EventCalendar $calendar,
        CalendarDate $proposedDeadline,
        CalendarDate $actualDeadline,
    ): void {
        $calendar->upsertDay($proposedDeadline, [
            'applicableTerm' => TermType::OBJECTION_PERIOD->value,
            'isATW' => true,
            'isLastDayOfBudget' => false,
            'isFirstDayOfBudget' => false,
        ]);

        $currentDate = $proposedDeadline->addDay();

        while ($currentDate->isBefore($actualDeadline)) {
            $calendar->upsertDay($currentDate, [
                'applicableTerm' => TermType::OBJECTION_PERIOD->value,
                'isBudgetDay' => false,
                'isATW' => true,
                'isFirstDayOfBudget' => false,
                'isLastDayOfBudget' => false,
            ]);
            $currentDate = $currentDate->addDay();
        }

        $calendar->upsertDay($actualDeadline, [
            'applicableTerm' => TermType::OBJECTION_PERIOD->value,
            'isBudgetDay' => false,
            'isATW' => false,
            'isLastDayOfBudget' => true,
            'isDeadline' => true,
            'isFirstDayOfBudget' => false,
        ]);
    }
}
