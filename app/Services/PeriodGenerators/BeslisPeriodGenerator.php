<?php

declare(strict_types=1);

namespace App\Services\PeriodGenerators;

use App\Enums\DecisionPeriodType;
use App\Enums\PetitionEventType;
use App\Enums\SuspensionType;
use App\Enums\TermType;
use App\Facades\LegalTermDeadlineCalculator;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\DecisionPeriodExtension;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PetitionEventData;
use Illuminate\Support\Collection;

use function array_any;
use function in_array;

/**
 * The decision period ("beslistermijn"). The receipt sets the statutory term, which starts the day
 * after it. Every extending event ("verdaging", "commissiezitting", "afspraak") pauses the statutory
 * term: its own days run from the day of the event, and the statutory term continues with the days
 * it has left once they are over. An extending event registered while an earlier one is still
 * running waits until that one is over.
 *
 * Every term counts its days the same way: suspended days and days frozen by an unspecified
 * adjournment do not spend budget, and a last day that is not a working day moves to the next one.
 */
class BeslisPeriodGenerator implements PeriodGeneratorInterface
{
    private const array EXTENDING_EVENT_TYPES = [
        PetitionEventType::MEETING_SCHEDULED,
        PetitionEventType::ADJOURNMENT,
        PetitionEventType::COMMITTEE_HEARING_DETERMINED,
    ];

    /**
     * @param Collection<int, PetitionEventData> $events
     */
    public function generate(Collection $events, EventCalendar $calendar): void
    {
        $receipt = $events->first(
            static fn(PetitionEventData $petitionEventData): bool => in_array(
                $petitionEventData->type,
                [
                    PetitionEventType::RECEIPT_OF_OBJECTION,
                    PetitionEventType::PETITION_RECEIVED,
                    PetitionEventType::RECEIPT_OF_COMPLAINT,
                ],
                true,
            ),
        );

        if (!$receipt || !$this->hasSufficientBudget($receipt)) {
            return;
        }

        $startDate = $this->calculateStartDate($calendar, $receipt);
        $extensions = $this->extensions($calendar, $events, $startDate);
        $statutoryLastDay = $this->addStatutoryTerm($calendar, $startDate, $receipt->duration ?? 0, $extensions);
        $lastDay = $statutoryLastDay;
        $lastDayType = DecisionPeriodType::STATUTORY;

        foreach ($extensions as $extension) {
            $this->addExtension($calendar, $extension);

            if (!$extension->lastDay->greaterThan($lastDay)) {
                continue;
            }

            $lastDay = $extension->lastDay;
            $lastDayType = $extension->type;
        }

        if (!$lastDay->equals($statutoryLastDay)) {
            $this->addStatutoryAtwDaysBeforeExtension($calendar, $statutoryLastDay);
        }

        $this->markDeadline($calendar, $lastDay, $lastDayType);
    }

    /**
     * A statutory term that ends on a day that is not a working day runs on to the next one, so an
     * extending event may still be registered on one of those days. When it is, the days between
     * the last statutory day and the extension stay part of the statutory term.
     */
    private function addStatutoryAtwDaysBeforeExtension(EventCalendar $calendar, CalendarDate $statutoryLastDay): void
    {
        $actualLastDay = LegalTermDeadlineCalculator::calculate($statutoryLastDay);

        if ($actualLastDay->equals($statutoryLastDay)) {
            return;
        }

        $calendar->upsertDay($statutoryLastDay, ['isATW' => true]);

        for ($date = $statutoryLastDay->addDay(); $date->isBefore($actualLastDay); $date = $date->addDay()) {
            if ($calendar->findDay($date)?->applicableTerm === TermType::DECISION_PERIOD->value) {
                continue;
            }

            $calendar->upsertDay($date, [
                'applicableTerm' => TermType::DECISION_PERIOD->value,
                'decisionPeriodType' => DecisionPeriodType::STATUTORY,
                'isBudgetDay' => false,
                'isATW' => true,
                'isFirstDayOfBudget' => false,
                'isLastDayOfBudget' => false,
            ]);
        }
    }

    private function hasSufficientBudget(PetitionEventData $receipt): bool
    {
        return ($receipt->duration ?? 0) > 0;
    }

    /**
     * The days of every extending event, in the order the events happened. A day already taken by
     * an earlier extending event is skipped, so the later one continues after it.
     *
     * @param Collection<int, PetitionEventData> $events
     *
     * @return list<DecisionPeriodExtension>
     */
    private function extensions(EventCalendar $calendar, Collection $events, CalendarDate $statutoryStartDate): array
    {
        $extensions = [];
        $takenDays = [];

        $extendingEvents = $events
            ->filter(static fn(PetitionEventData $event): bool => in_array($event->type, self::EXTENDING_EVENT_TYPES, true))
            ->sortBy(static fn(PetitionEventData $event): string => $event->date->toDateString());

        foreach ($extendingEvents as $event) {
            $term = $event->duration ?? 0;

            if ($term <= 0) {
                continue;
            }

            $days = [];
            $daysAdded = 0;
            $date = $event->date->greaterThan($statutoryStartDate) ? $event->date : $statutoryStartDate;

            for (; $daysAdded < $term; $date = $date->addDay()) {
                if (isset($takenDays[$date->toDateString()])) {
                    continue;
                }

                $isBudgetDay = $this->isBudgetDay($calendar, $date);
                $takenDays[$date->toDateString()] = true;
                $days[] = ['date' => $date, 'isBudgetDay' => $isBudgetDay];

                if ($isBudgetDay) {
                    $daysAdded++;
                }
            }

            $extensions[] = new DecisionPeriodExtension($this->decisionPeriodTypeOf($event->type), $days);
        }

        return $extensions;
    }

    private function decisionPeriodTypeOf(PetitionEventType $type): DecisionPeriodType
    {
        return match ($type) {
            PetitionEventType::MEETING_SCHEDULED => DecisionPeriodType::MEETING_SCHEDULED,
            PetitionEventType::COMMITTEE_HEARING_DETERMINED => DecisionPeriodType::COMMITTEE_HEARING,
            default => DecisionPeriodType::ADJOURNMENT,
        };
    }

    private function calculateStartDate(
        EventCalendar $calendar,
        PetitionEventData $receipt,
    ): CalendarDate {
        $receiptDate = $receipt->date;

        $lastObjectionDay = $calendar
            ->filter(static fn(EventCalendarDay $day): bool => $day->applicableTerm === TermType::OBJECTION_PERIOD->value)
            ->sortByDesc(static fn(EventCalendarDay $day): string => $day->date->toDateString())
            ->first();

        if (!$lastObjectionDay instanceof EventCalendarDay) {
            return $receiptDate->addDay();
        }

        $lastObjectionDate = $lastObjectionDay->date;

        return $receiptDate->greaterThan($lastObjectionDate)
            ? $receiptDate->addDay()
            : $lastObjectionDate->addDay();
    }

    /**
     * Writes the statutory term to the calendar, skipping the days of the extending events, and
     * returns its last budget day.
     *
     * @param list<DecisionPeriodExtension> $extensions
     */
    private function addStatutoryTerm(
        EventCalendar $calendar,
        CalendarDate $startDate,
        int $term,
        array $extensions,
    ): CalendarDate {
        $daysAdded = 0;
        $currentDate = $startDate;
        $lastDay = $startDate;

        for (; $daysAdded < $term; $currentDate = $currentDate->addDay()) {
            if ($this->isExtensionDay($extensions, $currentDate)) {
                continue;
            }

            if (!$this->isBudgetDay($calendar, $currentDate)) {
                $this->addDayWithoutBudget($calendar, $currentDate, DecisionPeriodType::STATUTORY);

                continue;
            }

            $this->addBudgetDay($calendar, $currentDate, DecisionPeriodType::STATUTORY, $daysAdded === 0, $daysAdded === $term - 1);
            $lastDay = $currentDate;
            $daysAdded++;
        }

        return $lastDay;
    }

    /**
     * @param list<DecisionPeriodExtension> $extensions
     */
    private function isExtensionDay(array $extensions, CalendarDate $date): bool
    {
        return array_any($extensions, static fn(DecisionPeriodExtension $extension): bool => $extension->contains($date));
    }

    private function addExtension(EventCalendar $calendar, DecisionPeriodExtension $extension): void
    {
        $isFirst = true;

        foreach ($extension->days as ['date' => $date, 'isBudgetDay' => $isBudgetDay]) {
            if (!$isBudgetDay) {
                $this->addDayWithoutBudget($calendar, $date, $extension->type);

                continue;
            }

            $this->addBudgetDay($calendar, $date, $extension->type, $isFirst, $date->equals($extension->lastDay));
            $isFirst = false;
        }
    }

    /**
     * Suspended days and days frozen by an unspecified adjournment do not spend budget.
     */
    private function isBudgetDay(EventCalendar $calendar, CalendarDate $date): bool
    {
        $day = $calendar->findDay($date);

        return !$day instanceof EventCalendarDay
            || (!$day->suspensionType instanceof SuspensionType && !$day->isUnspecifiedAdjournment);
    }

    private function addBudgetDay(
        EventCalendar $calendar,
        CalendarDate $date,
        DecisionPeriodType $type,
        bool $isFirst,
        bool $isLast,
    ): void {
        $calendar->upsertDay($date, [
            'applicableTerm' => TermType::DECISION_PERIOD->value,
            'decisionPeriodType' => $type,
            'isBudgetDay' => true,
            'isATW' => false,
            'isFirstDayOfBudget' => $isFirst,
            'isLastDayOfBudget' => $isLast,
        ]);
    }

    /**
     * A suspended day, or a day frozen by an unspecified adjournment, does not spend budget. It
     * shifts the term (and its deadline) forward, but stays part of the decision period.
     */
    private function addDayWithoutBudget(EventCalendar $calendar, CalendarDate $date, DecisionPeriodType $type): void
    {
        if ($this->isDaySuspended($calendar, $date)) {
            $calendar->totalDaysSuspended++;
        }

        $calendar->upsertDay($date, [
            'applicableTerm' => TermType::DECISION_PERIOD->value,
            'decisionPeriodType' => $type,
            'isBudgetDay' => false,
            'isATW' => false,
            'isFirstDayOfBudget' => false,
            'isLastDayOfBudget' => false,
        ]);
    }

    private function markDeadline(EventCalendar $calendar, CalendarDate $proposedDeadline, DecisionPeriodType $type): void
    {
        $actualDeadline = LegalTermDeadlineCalculator::calculate($proposedDeadline);

        if ($proposedDeadline->equals($actualDeadline)) {
            $calendar->upsertDay($proposedDeadline, [
                'isDeadline' => true,
            ]);

            return;
        }

        $this->addAtwDays($calendar, $proposedDeadline, $actualDeadline, $type);
    }

    private function isDaySuspended(EventCalendar $calendar, CalendarDate $date): bool
    {
        $existingDay = $calendar->findDay($date);

        return $existingDay instanceof EventCalendarDay && $existingDay->suspensionType instanceof SuspensionType;
    }

    private function addAtwDays(
        EventCalendar $calendar,
        CalendarDate $proposedDeadline,
        CalendarDate $actualDeadline,
        DecisionPeriodType $type,
    ): void {
        $calendar->upsertDay($proposedDeadline, [
            'applicableTerm' => TermType::DECISION_PERIOD->value,
            'decisionPeriodType' => $type,
            'isATW' => true,
            'isLastDayOfBudget' => false,
            'isFirstDayOfBudget' => false,
        ]);

        $currentDate = $proposedDeadline->addDay();
        while ($currentDate->isBefore($actualDeadline)) {
            $calendar->upsertDay($currentDate, [
                'applicableTerm' => TermType::DECISION_PERIOD->value,
                'decisionPeriodType' => $type,
                'isBudgetDay' => false,
                'isATW' => true,
                'isFirstDayOfBudget' => false,
                'isLastDayOfBudget' => false,
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
            'suspensionType' => null,
            'isFirstDayOfBudget' => false,
        ]);
    }
}
