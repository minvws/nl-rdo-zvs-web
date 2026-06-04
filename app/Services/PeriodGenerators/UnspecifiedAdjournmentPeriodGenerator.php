<?php

declare(strict_types=1);

namespace App\Services\PeriodGenerators;

use App\Enums\AdjournmentEndReason;
use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PetitionEventData;
use Illuminate\Support\Collection;

use function collect;

class UnspecifiedAdjournmentPeriodGenerator implements PeriodGeneratorInterface
{
    /**
     * @param Collection<int, PetitionEventData> $events
     */
    public function generate(Collection $events, EventCalendar $calendar): void
    {
        $startEvent = $this->findUnspecifiedAdjournmentStartEvent($events);
        if (!$startEvent instanceof PetitionEventData) {
            return;
        }

        $endEvent = $this->findUnspecifiedAdjournmentEndEvent($events);

        if (!$endEvent instanceof PetitionEventData) {
            $this->handleOpenAdjournment($calendar, $startEvent);

            return;
        }

        $this->handleClosedAdjournment($calendar, $startEvent, $endEvent);
    }

    /**
     * @param Collection<int, PetitionEventData> $events
     */
    private function findUnspecifiedAdjournmentStartEvent(Collection $events): ?PetitionEventData
    {
        /** @var PetitionEventData|null $petitionEventData */
        $petitionEventData = $events->first(
            static function (PetitionEventData $event): bool {
                return $event->type === PetitionEventType::UNSPECIFIED_ADJOURNMENT;
            },
        );

        return $petitionEventData;
    }

    /**
     * @param Collection<int, PetitionEventData> $events
     */
    private function findUnspecifiedAdjournmentEndEvent(Collection $events): ?PetitionEventData
    {
        /** @var PetitionEventData|null $petitionEventData */
        $petitionEventData = $events->first(
            static function (PetitionEventData $event): bool {
                return $event->type === PetitionEventType::UNSPECIFIED_ADJOURNMENT_END;
            },
        );

        return $petitionEventData;
    }

    private function handleOpenAdjournment(EventCalendar $calendar, PetitionEventData $startEvent): void
    {
        $this->markDecisionPeriodDaysAsAdjournment($calendar, $startEvent->date, null);
    }

    private function handleClosedAdjournment(
        EventCalendar $calendar,
        PetitionEventData $startEvent,
        PetitionEventData $endEvent,
    ): void {
        $this->markDecisionPeriodDaysAsAdjournment($calendar, $startEvent->date, $endEvent->date);
        $this->addAdjournmentBudgetDays($calendar, $startEvent, $endEvent);
        $this->setDeadlineOnLastDecisionPeriodDay($calendar);

        if ($endEvent->adjournmentEndReason === AdjournmentEndReason::Withdrawal) {
            $calendar->upsertDay($endEvent->date, ['isUnspecifiedAdjournmentWithdrawal' => true]);
        }
    }

    private function markDecisionPeriodDaysAsAdjournment(
        EventCalendar $calendar,
        CalendarDate $startDate,
        ?CalendarDate $endDate,
    ): void {
        foreach ($calendar as $day) {
            if (!$this->isDecisionPeriodDay($day)) {
                continue;
            }

            if (!$this->isWithinAdjournmentPeriod($day->date, $startDate, $endDate)) {
                continue;
            }

            $calendar->upsertDay($day->date, [
                'isUnspecifiedAdjournment' => true,
                'isDeadline' => false,
            ]);
        }
    }

    private function addAdjournmentBudgetDays(
        EventCalendar $calendar,
        PetitionEventData $startEvent,
        PetitionEventData $endEvent,
    ): void {
        $budget = $startEvent->duration ?? 0;
        if ($budget === 0) {
            return;
        }

        $this->clearExistingDecisionPeriodDeadlines($calendar);
        $this->generateBudgetDays($calendar, $endEvent->date->addDay(), $budget);
    }

    private function clearExistingDecisionPeriodDeadlines(EventCalendar $calendar): void
    {
        foreach ($calendar as $day) {
            if ($this->isDecisionPeriodDay($day) && $day->isDeadline) {
                $calendar->upsertDay($day->date, ['isDeadline' => false]);
            }
        }
    }

    private function generateBudgetDays(EventCalendar $calendar, CalendarDate $startDate, int $budget): void
    {
        $currentDate = $startDate;

        for ($dayIndex = 0; $dayIndex < $budget; $dayIndex++) {
            $calendar->upsertDay($currentDate, [
                'applicableTerm' => TermType::DECISION_PERIOD->value,
                'isBudgetDay' => true,
                'isFirstDayOfBudget' => $dayIndex === 0,
                'isLastDayOfBudget' => $dayIndex === $budget - 1,
            ]);
            $currentDate = $currentDate->addDay();
        }
    }

    private function setDeadlineOnLastDecisionPeriodDay(EventCalendar $calendar): void
    {
        $lastDay = $this->findLastDecisionPeriodDay($calendar);
        if (!$lastDay instanceof EventCalendarDay) {
            return;
        }

        $calendar->upsertDay($lastDay->date, ['isDeadline' => true]);
    }

    private function findLastDecisionPeriodDay(EventCalendar $calendar): ?EventCalendarDay
    {
        return collect($calendar->all())
            ->filter(function (EventCalendarDay $day): bool {
                return $this->isDecisionPeriodDay($day);
            })
            ->sortByDesc(static function (EventCalendarDay $day): string {
                return $day->date->toDateString();
            })
            ->first();
    }

    private function isDecisionPeriodDay(EventCalendarDay $day): bool
    {
        return $day->applicableTerm === TermType::DECISION_PERIOD->value;
    }

    private function isWithinAdjournmentPeriod(
        CalendarDate $date,
        CalendarDate $startDate,
        ?CalendarDate $endDate,
    ): bool {
        $afterStart = $date->greaterThanOrEqualTo($startDate);

        if (!$endDate instanceof CalendarDate) {
            return $afterStart;
        }

        $beforeEnd = $date->lessThanOrEqualTo($endDate->addDays(-1));

        return $afterStart && $beforeEnd;
    }
}
