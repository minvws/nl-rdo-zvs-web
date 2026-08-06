<?php

declare(strict_types=1);

namespace App\Services\PeriodGenerators;

use App\Enums\AdjournmentEndReason;
use App\Enums\PetitionEventType;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\PetitionEventData;
use Illuminate\Support\Collection;

/**
 * An unspecified adjournment ("ongespecificeerde aanhouding") pauses the running term for
 * an unknown length. This generator only marks the frozen days; it runs before the objection
 * and decision period generators, which skip frozen days when spending their budget. Freezing
 * a day therefore shifts the running term (and its deadline) one day forward.
 *
 * The frozen range is [start, end): the day the adjournment ends ('gebeurtenis heeft
 * plaatsgevonden' / 'intrekking akkoord') counts towards the term again, just like the day a
 * decision on objection is sent counts for an IGS penalty. While an adjournment is still open
 * the range runs up to today, which is why the term keeps extending by a day for every day the
 * adjournment stays open.
 *
 * Multiple adjournment cycles are supported by pairing starts and ends in chronological order.
 */
class UnspecifiedAdjournmentPeriodGenerator implements PeriodGeneratorInterface
{
    /**
     * @param Collection<int, PetitionEventData> $events
     */
    public function generate(Collection $events, EventCalendar $calendar): void
    {
        $starts = $this->sortedEventsOfType($events, PetitionEventType::UNSPECIFIED_ADJOURNMENT);
        $ends = $this->sortedEventsOfType($events, PetitionEventType::UNSPECIFIED_ADJOURNMENT_END);

        $starts->each(function (PetitionEventData $start, int $index) use ($ends, $calendar): void {
            $this->freezeAdjournment($calendar, $start, $ends->get($index));
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

    private function freezeAdjournment(
        EventCalendar $calendar,
        PetitionEventData $start,
        ?PetitionEventData $end,
    ): void {
        // While the adjournment is still open the freeze runs up to (but not including) today,
        // so the deadline keeps moving forward for every day it stays open.
        $endDate = $end instanceof PetitionEventData ? $end->date : CalendarDate::today();

        $currentDate = $start->date;
        while ($currentDate->isBefore($endDate)) {
            $calendar->upsertDay($currentDate, ['isUnspecifiedAdjournment' => true]);
            $currentDate = $currentDate->addDay();
        }

        if ($end instanceof PetitionEventData && $end->reasoning === AdjournmentEndReason::Withdrawal->value) {
            $calendar->upsertDay($end->date, ['isUnspecifiedAdjournmentWithdrawal' => true]);
        }
    }
}
