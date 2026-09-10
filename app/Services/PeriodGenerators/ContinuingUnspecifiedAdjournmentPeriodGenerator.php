<?php

declare(strict_types=1);

namespace App\Services\PeriodGenerators;

use App\Enums\AdjournmentEndReason;
use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\Facades\LegalTermDeadlineCalculator;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PetitionEventData;
use Illuminate\Support\Collection;

use function max;

/**
 * An unspecified adjournment ("ongespecificeerde aanhouding") for departments on
 * UnspecifiedAdjournmentMode::CONTINUES_TERMS. It leaves the running terms alone: both the
 * objection and the decision period keep spending their budget while it is open, which is why
 * this generator runs after them instead of before.
 *
 * What it does change is the decision period deadline. While the adjournment is open there is
 * no deadline at all. Ending it starts a fresh decision term on the day it ends, so that day
 * counts as the first day of the new term. The length of that term comes from the two day
 * counts registered at the start: the "gebeurtenis" one, or the "intrekking" one when the
 * adjournment was withdrawn. If the original decision term is still running and has more days
 * left than the registered term, the longer of the two wins and the other is ignored.
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

        $this->markAdjournmentDays($calendar, $start->date, $end?->date);

        if (!$end instanceof PetitionEventData) {
            $this->clearDecisionPeriodDeadlines($calendar);

            return;
        }

        if ($end->reasoning === AdjournmentEndReason::Withdrawal->value) {
            $calendar->upsertDay($end->date, ['isUnspecifiedAdjournmentWithdrawal' => true]);
        }

        $this->restartDecisionPeriod($calendar, $start, $end, $originalDeadline);
    }

    /**
     * Flags the days the adjournment covers and drops any deadline that falls inside them. The
     * day the adjournment ends is left alone: it belongs to the new decision term.
     */
    private function markAdjournmentDays(
        EventCalendar $calendar,
        CalendarDate $startDate,
        ?CalendarDate $endDate,
    ): void {
        $lastDay = $endDate instanceof CalendarDate ? $endDate->subDay() : CalendarDate::today();

        foreach ($this->datesInRange($calendar, $startDate, $lastDay) as $date) {
            $calendar->upsertDay($date, [
                'isUnspecifiedAdjournment' => true,
                'isDeadline' => false,
            ]);
        }
    }

    /**
     * @return array<int, CalendarDate>
     */
    private function datesInRange(EventCalendar $calendar, CalendarDate $from, CalendarDate $until): array
    {
        $dates = [];

        foreach ($calendar as $day) {
            if ($day->date->isBefore($from)) {
                continue;
            }
            if ($day->date->greaterThan($until)) {
                continue;
            }
            $dates[] = $day->date;
        }

        return $dates;
    }

    private function restartDecisionPeriod(
        EventCalendar $calendar,
        PetitionEventData $start,
        PetitionEventData $end,
        ?CalendarDate $originalDeadline,
    ): void {
        $startDate = $this->newTermStartDate($calendar, $end->date);
        $term = $this->resolveTerm($start, $end, $startDate, $originalDeadline);

        if ($term <= 0) {
            return;
        }

        $this->clearDecisionPeriodDeadlines($calendar);
        $this->generateDecisionPeriodDays($calendar, $startDate, $term);
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

    private function resolveTerm(
        PetitionEventData $start,
        PetitionEventData $end,
        CalendarDate $startDate,
        ?CalendarDate $originalDeadline,
    ): int {
        $registeredTerm = $end->reasoning === AdjournmentEndReason::Withdrawal->value
            ? ($start->withdrawalDuration ?? 0)
            : ($start->duration ?? 0);

        return max($registeredTerm, $this->remainingOriginalTerm($startDate, $originalDeadline));
    }

    /**
     * An original decision term that has not expired yet keeps the days it has left, counted
     * from the first day of the new term. An expired one contributes nothing.
     */
    private function remainingOriginalTerm(CalendarDate $startDate, ?CalendarDate $originalDeadline): int
    {
        if (!$originalDeadline instanceof CalendarDate || $originalDeadline->isBefore($startDate)) {
            return 0;
        }

        return $startDate->diffInDays($originalDeadline) + 1;
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

    private function generateDecisionPeriodDays(EventCalendar $calendar, CalendarDate $startDate, int $term): void
    {
        for ($dayIndex = 0; $dayIndex < $term; $dayIndex++) {
            $calendar->upsertDay($startDate->addDays($dayIndex), [
                'applicableTerm' => TermType::DECISION_PERIOD->value,
                'isUnspecifiedAdjournment' => false,
                'isBudgetDay' => true,
                'isFirstDayOfBudget' => $dayIndex === 0,
                'isLastDayOfBudget' => $dayIndex === $term - 1,
                'isDeadline' => false,
            ]);
        }

        $this->markDeadline($calendar, $startDate->addDays($term - 1));
    }

    private function markDeadline(EventCalendar $calendar, CalendarDate $proposedDeadline): void
    {
        $actualDeadline = LegalTermDeadlineCalculator::calculate($proposedDeadline);

        if ($proposedDeadline->equals($actualDeadline)) {
            $calendar->upsertDay($proposedDeadline, ['isDeadline' => true]);

            return;
        }

        $calendar->upsertDay($proposedDeadline, [
            'applicableTerm' => TermType::DECISION_PERIOD->value,
            'isATW' => true,
            'isLastDayOfBudget' => false,
        ]);

        $currentDate = $proposedDeadline->addDay();
        while ($currentDate->isBefore($actualDeadline)) {
            $calendar->upsertDay($currentDate, [
                'applicableTerm' => TermType::DECISION_PERIOD->value,
                'isBudgetDay' => false,
                'isATW' => true,
            ]);
            $currentDate = $currentDate->addDay();
        }

        $calendar->upsertDay($actualDeadline, [
            'applicableTerm' => TermType::DECISION_PERIOD->value,
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
