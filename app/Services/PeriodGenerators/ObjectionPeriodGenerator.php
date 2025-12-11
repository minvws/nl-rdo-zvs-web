<?php

declare(strict_types=1);

namespace App\Services\PeriodGenerators;

use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\Facades\LegalTermDeadlineCalculator;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\PetitionEventData;
use Illuminate\Support\Collection;

class ObjectionPeriodGenerator implements PeriodGeneratorInterface
{
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
        $proposedDeadline = $startDate->addDays($budget - 1);
        $actualDeadline = LegalTermDeadlineCalculator::calculate($proposedDeadline);

        $this->addBudgetDays($calendar, $startDate, $budget, $proposedDeadline, $actualDeadline);
        $this->addAtwDaysIfDeadlineMoved($calendar, $proposedDeadline, $actualDeadline);
    }

    private function addBudgetDays(
        EventCalendar $calendar,
        CalendarDate $startDate,
        int $budget,
        CalendarDate $proposedDeadline,
        CalendarDate $actualDeadline,
    ): void {
        $deadlineNotMoved = $proposedDeadline->equals($actualDeadline);

        for ($i = 0; $i < $budget; $i++) {
            $date = $startDate->addDays($i);
            $isFirst = $i === 0;
            $isLast = ($i === $budget - 1) && $deadlineNotMoved;

            $calendar->upsertDay($date, [
                'applicableTerm' => TermType::OBJECTION_PERIOD->value,
                'isBudgetDay' => true,
                'isFirstDayOfBudget' => $isFirst,
                'isLastDayOfBudget' => $isLast,
                'isDeadline' => $isLast,
            ]);
        }
    }

    private function addAtwDaysIfDeadlineMoved(
        EventCalendar $calendar,
        CalendarDate $proposedDeadline,
        CalendarDate $actualDeadline,
    ): void {
        if ($proposedDeadline->equals($actualDeadline)) {
            return;
        }

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
