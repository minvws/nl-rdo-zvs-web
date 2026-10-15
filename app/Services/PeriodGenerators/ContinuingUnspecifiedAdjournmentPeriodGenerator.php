<?php

declare(strict_types=1);

namespace App\Services\PeriodGenerators;

use App\Enums\AdjournmentEndReason;
use App\Enums\DecisionPeriodType;
use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\Facades\LegalTermDeadlineCalculator;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PetitionEventData;
use Illuminate\Support\Collection;

/**
 * An unspecified adjournment ("ongespecificeerde aanhouding") for departments on
 * UnspecifiedAdjournmentMode::CONTINUES_TERMS. It leaves the running terms alone: both the
 * objection and the decision period keep spending their budget while it is open, which is why
 * this generator runs after them instead of before.
 *
 * While the adjournment is open nothing about the terms changes, because it is not known yet
 * whether it ends with a "gebeurtenis" or an "intrekking". Once the original decision term has
 * run out, the days the adjournment is still waiting are shown as their own period.
 *
 * Ending it may extend the decision term. A new term starts on the day it ends, using the day
 * count registered for how it ended. It only replaces the original term when its last day falls
 * after the original one; the original term and its last day are then kept visible as such.
 */
class ContinuingUnspecifiedAdjournmentPeriodGenerator implements PeriodGeneratorInterface
{
    /**
     * @param Collection<int, PetitionEventData> $events
     */
    public function generate(Collection $events, EventCalendar $calendar): void
    {
        $starts = $this->sortedEventsOfType($events, PetitionEventType::UNSPECIFIED_ADJOURNMENT);
        $ends = $this->sortedEventsOfType($events, PetitionEventType::UNSPECIFIED_ADJOURNMENT_END);

        $starts->each(function (PetitionEventData $start, int $index) use ($ends, $calendar): void {
            $this->applyAdjournment($calendar, $start, $ends->get($index));
        });
    }

    /**
     * @param Collection<int, PetitionEventData> $events
     *
     * @return Collection<int, PetitionEventData>
     */
    private function sortedEventsOfType(Collection $events, PetitionEventType $type): Collection
    {
        return $events
            ->filter(static fn(PetitionEventData $event): bool => $event->type === $type)
            ->sortBy(static fn(PetitionEventData $event): string => $event->date->toDateString())
            ->values();
    }

    private function applyAdjournment(
        EventCalendar $calendar,
        PetitionEventData $start,
        ?PetitionEventData $end,
    ): void {
        $originalDeadline = $this->decisionPeriodDeadline($calendar);
        $lastAdjournedDay = $end instanceof PetitionEventData ? $end->date->subDay() : CalendarDate::today();

        $this->addWaitingDays($calendar, $start->date, $lastAdjournedDay, $originalDeadline);

        if (!$end instanceof PetitionEventData) {
            $calendar->upsertDay(CalendarDate::today(), ['isOpenUnspecifiedAdjournment' => true]);

            return;
        }

        if ($end->reasoning === AdjournmentEndReason::Withdrawal->value) {
            $calendar->upsertDay($end->date, ['isUnspecifiedAdjournmentWithdrawal' => true]);
        }

        $this->extendDecisionPeriod($calendar, $start, $end, $originalDeadline);
    }

    /**
     * Days after the original decision term that the adjournment is still waiting on extend the
     * decision term until it ends. Days that already belong to another term, such as a penalty period, keep that term. The days of the
     * running terms are not flagged at all: nothing changes there, so they must fold the same way
     * as they would without the adjournment.
     */
    private function addWaitingDays(
        EventCalendar $calendar,
        CalendarDate $startDate,
        CalendarDate $lastDay,
        ?CalendarDate $originalDeadline,
    ): void {
        if (!$originalDeadline instanceof CalendarDate) {
            return;
        }

        $date = $startDate->greaterThan($originalDeadline) ? $startDate : $originalDeadline->addDay();

        for (; $date->lessThanOrEqualTo($lastDay); $date = $date->addDay()) {
            if ($calendar->findDay($date)?->applicableTerm !== null) {
                continue;
            }

            $calendar->upsertDay($date, [
                'applicableTerm' => TermType::DECISION_PERIOD->value,
                'decisionPeriodType' => DecisionPeriodType::EXTENDED,
                'isUnspecifiedAdjournment' => true,
            ]);
        }
    }

    private function extendDecisionPeriod(
        EventCalendar $calendar,
        PetitionEventData $start,
        PetitionEventData $end,
        ?CalendarDate $originalDeadline,
    ): void {
        $term = $this->registeredTerm($start, $end);

        if ($term <= 0) {
            return;
        }

        $startDate = $this->newTermStartDate($calendar, $end->date);
        $newDeadline = LegalTermDeadlineCalculator::calculate($startDate->addDays($term - 1));

        if ($originalDeadline instanceof CalendarDate && !$newDeadline->greaterThan($originalDeadline)) {
            return;
        }

        $isStatutoryDeadline = $this->isStatutoryDeadline($calendar, $originalDeadline);

        $this->clearDecisionPeriodDeadlines($calendar);
        $this->generateDecisionPeriodDays($calendar, $startDate, $term, $this->newTermType($end));
        $calendar->upsertDay($end->date, ['appliedUnspecifiedAdjournmentTerm' => $term]);

        if ($isStatutoryDeadline && $originalDeadline instanceof CalendarDate) {
            $calendar->upsertDay($originalDeadline, ['isOriginalDeadline' => true]);
        }
    }

    /**
     * A last day set by an extending event or an earlier adjournment is not the statutory one, so
     * an adjournment that extends the term again leaves it unlabelled.
     */
    private function isStatutoryDeadline(EventCalendar $calendar, ?CalendarDate $deadline): bool
    {
        if (!$deadline instanceof CalendarDate) {
            return false;
        }

        return $calendar->findDay($deadline)?->decisionPeriodType === DecisionPeriodType::STATUTORY;
    }

    /**
     * The bezwaartermijn always runs to its own end, so a decision term that would start inside
     * it waits until the day after the objection period is over.
     */
    private function newTermStartDate(EventCalendar $calendar, CalendarDate $endDate): CalendarDate
    {
        $lastObjectionDay = $calendar
            ->filter(static fn(EventCalendarDay $day): bool => $day->applicableTerm === TermType::OBJECTION_PERIOD->value)
            ->sortByDesc(static fn(EventCalendarDay $day): string => $day->date->toDateString())
            ->first();

        if (!$lastObjectionDay instanceof EventCalendarDay || $lastObjectionDay->date->isBefore($endDate)) {
            return $endDate;
        }

        return $lastObjectionDay->date->addDay();
    }

    private function registeredTerm(PetitionEventData $start, PetitionEventData $end): int
    {
        return $end->reasoning === AdjournmentEndReason::Withdrawal->value
            ? ($start->withdrawalDuration ?? 0)
            : ($start->duration ?? 0);
    }

    private function newTermType(PetitionEventData $end): DecisionPeriodType
    {
        return $end->reasoning === AdjournmentEndReason::Withdrawal->value
            ? DecisionPeriodType::WITHDRAWAL
            : DecisionPeriodType::EVENT;
    }

    private function decisionPeriodDeadline(EventCalendar $calendar): ?CalendarDate
    {
        $deadlineDay = $calendar
            ->filter(fn(EventCalendarDay $day): bool => $day->isDeadline && $this->isDecisionPeriodDay($day))
            ->sortByDesc(static fn(EventCalendarDay $day): string => $day->date->toDateString())
            ->first();

        return $deadlineDay instanceof EventCalendarDay ? $deadlineDay->date : null;
    }

    private function clearDecisionPeriodDeadlines(EventCalendar $calendar): void
    {
        foreach ($calendar as $day) {
            if (!$day->isDeadline) {
                continue;
            }
            if (!$this->isDecisionPeriodDay($day)) {
                continue;
            }
            $calendar->upsertDay($day->date, ['isDeadline' => false]);
        }
    }

    private function generateDecisionPeriodDays(
        EventCalendar $calendar,
        CalendarDate $startDate,
        int $term,
        DecisionPeriodType $type,
    ): void {
        for ($dayIndex = 0; $dayIndex < $term; $dayIndex++) {
            $calendar->upsertDay($startDate->addDays($dayIndex), [
                'applicableTerm' => TermType::DECISION_PERIOD->value,
                'decisionPeriodType' => $type,
                'isUnspecifiedAdjournment' => false,
                'isBudgetDay' => true,
                'isATW' => false,
                'isFirstDayOfBudget' => $dayIndex === 0,
                'isLastDayOfBudget' => $dayIndex === $term - 1,
                'isDeadline' => false,
            ]);
        }

        $this->markDeadline($calendar, $startDate->addDays($term - 1), $type);
    }

    private function markDeadline(EventCalendar $calendar, CalendarDate $proposedDeadline, DecisionPeriodType $type): void
    {
        $actualDeadline = LegalTermDeadlineCalculator::calculate($proposedDeadline);

        if ($proposedDeadline->equals($actualDeadline)) {
            $calendar->upsertDay($proposedDeadline, ['isDeadline' => true]);

            return;
        }

        $calendar->upsertDay($proposedDeadline, [
            'applicableTerm' => TermType::DECISION_PERIOD->value,
            'decisionPeriodType' => $type,
            'isATW' => true,
            'isLastDayOfBudget' => false,
        ]);

        $currentDate = $proposedDeadline->addDay();
        while ($currentDate->isBefore($actualDeadline)) {
            $calendar->upsertDay($currentDate, [
                'applicableTerm' => TermType::DECISION_PERIOD->value,
                'decisionPeriodType' => $type,
                'isBudgetDay' => false,
                'isATW' => true,
            ]);
            $currentDate = $currentDate->addDay();
        }

        $calendar->upsertDay($actualDeadline, [
            'applicableTerm' => TermType::DECISION_PERIOD->value,
            'decisionPeriodType' => $type,
            'isBudgetDay' => false,
            'isATW' => false,
            'isLastDayOfBudget' => true,
            'isDeadline' => true,
        ]);
    }

    private function isDecisionPeriodDay(EventCalendarDay $day): bool
    {
        return $day->applicableTerm === TermType::DECISION_PERIOD->value;
    }
}
