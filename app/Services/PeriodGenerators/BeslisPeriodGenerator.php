<?php

declare(strict_types=1);

namespace App\Services\PeriodGenerators;

use App\Enums\PetitionEventType;
use App\Enums\SuspensionType;
use App\Enums\TermType;
use App\Facades\LegalTermDeadlineCalculator;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PetitionEventData;
use Illuminate\Support\Collection;

class BeslisPeriodGenerator implements PeriodGeneratorInterface
{
    private int $daysAdded = 0;
    private bool $firstDaySet = false;

    /**
     * @param Collection<int, PetitionEventData> $events
     */
    public function generate(Collection $events, EventCalendar $calendar): void
    {
        $receipt = $events->first(
            static function (PetitionEventData $petitionEventData): bool {
                return
                    $petitionEventData->type === PetitionEventType::RECEIPT_OF_OBJECTION ||
                    $petitionEventData->type === PetitionEventType::PETITION_RECEIVED;
            },
        );

        if (!$receipt || !$this->hasSufficientBudget($receipt)) {
            return;
        }

        $budget = $this->calculateTotalBudget($events, $receipt->duration ?? 0);
        $startDate = $this->calculateStartDate($calendar, $receipt);

        $this->addBudgetDaysExcludingSuspensions($calendar, $startDate, $budget);
    }

    private function hasSufficientBudget(PetitionEventData $receipt): bool
    {
        return ($receipt->duration ?? 0) > 0;
    }

    /**
     * @param Collection<int, PetitionEventData> $events
     */
    private function calculateTotalBudget(Collection $events, int $initialBudget): int
    {
        $hearingDateEvents = $events->filter(
            static function (PetitionEventData $petitionEventData): bool {
                return $petitionEventData->type === PetitionEventType::MEETING_SCHEDULED;
            },
        );

        $adjournmentDateEvents = $events->filter(
            static function (PetitionEventData $petitionEventData): bool {
                return $petitionEventData->type === PetitionEventType::ADJOURNMENT;
            },
        );

        $additionalBudget
            = $hearingDateEvents->sum(static fn(PetitionEventData $event): int => $event->duration ?? 0)
            + $adjournmentDateEvents->sum(static fn(PetitionEventData $event): int => $event->duration ?? 0);

        return $initialBudget + $additionalBudget;
    }

    private function calculateStartDate(
        EventCalendar $calendar,
        PetitionEventData $receipt,
    ): CalendarDate {
        $receiptDate = $receipt->date;

        $lastObjectionDay = $calendar
            ->filter(static fn($day): bool => $day->applicableTerm === TermType::OBJECTION_PERIOD->value)
            ->sortByDesc(static fn($day): string => $day->date->toDateString())
            ->first();

        if (!$lastObjectionDay) {
            return $receiptDate->addDay();
        }

        $lastObjectionDate = $lastObjectionDay->date;

        return $receiptDate->greaterThan($lastObjectionDate)
            ? $receiptDate->addDay()
            : $lastObjectionDate->addDay();
    }

    private function addBudgetDaysExcludingSuspensions(
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
        if ($this->isDaySuspended($calendar, $currentDate)) {
            $calendar->totalDaysSuspended++;
            $calendar->upsertDay($currentDate, [
                'applicableTerm' => TermType::DECISION_PERIOD->value,
                'isBudgetDay' => false,
                'isFirstDayOfBudget' => false,
                'isLastDayOfBudget' => false,
            ]);

            return;
        }

        // A day frozen by an unspecified adjournment does not spend decision-period budget,
        // shifting the term (and its deadline) forward, but stays part of the decision period.
        if ($this->isDayFrozen($calendar, $currentDate)) {
            $calendar->upsertDay($currentDate, [
                'applicableTerm' => TermType::DECISION_PERIOD->value,
                'isBudgetDay' => false,
                'isFirstDayOfBudget' => false,
                'isLastDayOfBudget' => false,
            ]);

            return;
        }

        $isFirst = !$this->firstDaySet;
        $isLast = $this->daysAdded === $budget - 1;

        $calendar->upsertDay($currentDate, [
            'applicableTerm' => TermType::DECISION_PERIOD->value,
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

    private function isDaySuspended(EventCalendar $calendar, CalendarDate $date): bool
    {
        $existingDay = $calendar->findDay($date);

        return $existingDay instanceof EventCalendarDay && $existingDay->suspensionType instanceof SuspensionType;
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
            'applicableTerm' => TermType::DECISION_PERIOD->value,
            'isATW' => true,
            'isLastDayOfBudget' => false,
            'isFirstDayOfBudget' => false,
        ]);

        $currentDate = $proposedDeadline->addDay();
        while ($currentDate->isBefore($actualDeadline)) {
            $calendar->upsertDay($currentDate, [
                'applicableTerm' => TermType::DECISION_PERIOD->value,
                'isBudgetDay' => false,
                'isATW' => true,
                'isFirstDayOfBudget' => false,
                'isLastDayOfBudget' => false,
            ]);
            $currentDate = $currentDate->addDay();
        }

        $calendar->upsertDay($actualDeadline, [
            'applicableTerm' => TermType::DECISION_PERIOD->value,
            'isBudgetDay' => false,
            'isATW' => false,
            'isLastDayOfBudget' => true,
            'isDeadline' => true,
            'suspensionType' => null,
            'isFirstDayOfBudget' => false,
        ]);
    }
}
