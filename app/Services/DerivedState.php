<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\Enums\UnspecifiedAdjournmentMode;
use App\Services\PeriodGenerators\BeslisPeriodGenerator;
use App\Services\PeriodGenerators\BNTPeriodGenerator;
use App\Services\PeriodGenerators\ContinuingUnspecifiedAdjournmentPeriodGenerator;
use App\Services\PeriodGenerators\EventsGenerator;
use App\Services\PeriodGenerators\FinalDecisionDateGenerator;
use App\Services\PeriodGenerators\IGSPeriodGenerator;
use App\Services\PeriodGenerators\ObjectionPeriodGenerator;
use App\Services\PeriodGenerators\PeriodGeneratorInterface;
use App\Services\PeriodGenerators\SuspensionPeriodGenerator;
use App\Services\PeriodGenerators\UnspecifiedAdjournmentPeriodGenerator;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PetitionEventData;
use Illuminate\Support\Collection;

use function collect;
use function in_array;

class DerivedState
{
    /** @var Collection<int, PetitionEventData> */
    private Collection $events;
    private EventCalendar $calendar;
    private UnspecifiedAdjournmentMode $unspecifiedAdjournmentMode = UnspecifiedAdjournmentMode::FREEZES_TERMS;

    public function withUnspecifiedAdjournmentMode(UnspecifiedAdjournmentMode $mode): self
    {
        $this->unspecifiedAdjournmentMode = $mode;

        return $this;
    }

    /**
     * @param Collection<int, PetitionEventData> $events
     */
    public function addEvents(Collection $events): self
    {
        $this->events = $events;

        return $this;
    }

    public function buildCalendar(): self
    {
        $this->calendar = new EventCalendar();
        $generators = $this->defaultGenerators();
        foreach ($generators as $generator) {
            $generator->generate($this->events, $this->calendar);
        }
        $this->calendar->sortByDate();

        return $this;
    }

    public function isOpschorting(CalendarDate $date): bool
    {
        return $this->calendar->isOpschorting($date);
    }

    public function getCalendar(): EventCalendar
    {
        return $this->calendar;
    }

    /**
     * @return Collection<int, PetitionEventData>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function findDay(CalendarDate $date): ?EventCalendarDay
    {
        return $this->calendar->findDay($date);
    }

    public function forfeited(string $date): int
    {
        $targetDate = CalendarDate::create($date);

        return $this->calendar
            ->filter(static function ($day) use ($targetDate): bool {
                /** @var EventCalendarDay $day */
                return $day->date->lessThanOrEqualTo($targetDate);
            })
            ->sum(static function ($day): int {
                /** @var EventCalendarDay $day */
                return $day->penaltyTodayInEuros;
            });
    }

    public function dateOfEntry(): ?CalendarDate
    {
        $receiptEvent = $this->events->first(
            static fn(PetitionEventData $event): bool => in_array(
                $event->type,
                [
                    PetitionEventType::RECEIPT_OF_OBJECTION,
                    PetitionEventType::PETITION_RECEIVED,
                    PetitionEventType::RECEIPT_OF_COMPLAINT,
                ],
                true,
            ),
        );

        return $receiptEvent?->date;
    }

    public function deadlineDate(): ?CalendarDate
    {
        if (
            $this->events->contains(
                static fn(PetitionEventData $event): bool => $event->type === PetitionEventType::FINAL_RESULT,
            )
        ) {
            return null;
        }

        // While an unspecified adjournment is still open its end date is "today", so the running
        // term has no fixed last day yet. The petition deadline is therefore today (and keeps
        // moving forward daily), decoupled from the projected last-day marker in the calendar.
        // Departments that keep their terms running have no deadline at all until it ends.
        if ($this->hasOpenUnspecifiedAdjournment()) {
            return $this->unspecifiedAdjournmentMode === UnspecifiedAdjournmentMode::FREEZES_TERMS
                ? CalendarDate::today()
                : null;
        }

        $lastDeadlineDay = $this->calendar
            ->filter(static fn($day): bool => $day->isDeadline)
            ->sortByDesc(static fn($day): string => $day->date->toDateString())
            ->first();

        $deadline = $lastDeadlineDay?->date;

        foreach ([TermType::NOTICE_OF_DEFAULT, TermType::APPEAL_NOT_TIMELY] as $penaltyTerm) {
            $penaltyEnd = $this->activePenaltyPeriodEndDateForTerm($penaltyTerm);

            if ($penaltyEnd instanceof CalendarDate && ($deadline === null || $penaltyEnd->greaterThan($deadline))) {
                $deadline = $penaltyEnd;
            }
        }

        return $deadline;
    }

    public function deadlineDateForTerm(TermType $term): ?CalendarDate
    {
        $deadlineDay = $this->calendar
            ->filter(static fn($day): bool => $day->isDeadline && $day->applicableTerm === $term->value)
            ->sortByDesc(static fn($day): string => $day->date->toDateString())
            ->first();

        return $deadlineDay?->date;
    }

    public function penaltyPeriodEndDateForTerm(TermType $term): ?CalendarDate
    {
        $penaltyEndDay = $this->calendar
            ->filter(static fn($day): bool => $day->applicableTerm === TermType::PENALTY_PERIOD->value
                && $day->penaltySourceTerm === $term->value
                && $day->penaltyTodayInEuros > 0)
            ->sortByDesc(static fn($day): string => $day->date->toDateString())
            ->first();

        return $penaltyEndDay?->date;
    }

    public function penaltyTodayForTerm(CalendarDate $date, TermType $term): int
    {
        $day = $this->calendar->findDay($date);

        if (!$day instanceof EventCalendarDay) {
            return 0;
        }

        if ($day->penaltySourceTerm !== $term->value) {
            return 0;
        }

        return $day->penaltyTodayInEuros;
    }

    public function forfeitedForTerm(CalendarDate $date, TermType $term): int
    {
        return $this->calendar
            ->filter(static fn(EventCalendarDay $day): bool => $day->date->lessThanOrEqualTo($date)
                && $day->penaltySourceTerm === $term->value)
            ->sum(static fn(EventCalendarDay $day): int => $day->penaltyTodayInEuros);
    }

    public function maximumPenaltyForEventType(PetitionEventType $eventType): int
    {
        $eventsOfType = $this->events->filter(
            static fn(PetitionEventData $e): bool => $e->type === $eventType,
        );

        if ($eventsOfType->isEmpty()) {
            return 0;
        }

        return $eventsOfType
            ->sum(static fn(PetitionEventData $event): int => collect($event->penalties)
                ->sum(static fn($penalty): int => $penalty->amount * $penalty->duration));
    }

    private function hasOpenUnspecifiedAdjournment(): bool
    {
        $countOfType = fn(PetitionEventType $type): int => $this->events
            ->filter(static fn(PetitionEventData $event): bool => $event->type === $type)
            ->count();

        return $countOfType(PetitionEventType::UNSPECIFIED_ADJOURNMENT)
            > $countOfType(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END);
    }

    /**
     * Once the decision deadline for a penalty term has passed, the date on which the penalties stop
     * accruing becomes the relevant deadline.
     */
    private function activePenaltyPeriodEndDateForTerm(TermType $term): ?CalendarDate
    {
        $termDeadline = $this->deadlineDateForTerm($term);

        if ($termDeadline instanceof CalendarDate && $termDeadline->isInThePast()) {
            return $this->penaltyPeriodEndDateForTerm($term);
        }

        return null;
    }

    /**
     * @return array<int, PeriodGeneratorInterface>
     */
    private function defaultGenerators(): array
    {
        // I think we should provide the generators from the outside
        // maybe resolve them from config
        if ($this->unspecifiedAdjournmentMode === UnspecifiedAdjournmentMode::CONTINUES_TERMS) {
            // The adjournment must not eat into the objection and decision budgets, so it runs
            // after both of them and only rewrites the decision period deadline afterwards.
            return [
                new SuspensionPeriodGenerator(),
                new ObjectionPeriodGenerator($this->unspecifiedAdjournmentMode),
                new BeslisPeriodGenerator(),
                new IGSPeriodGenerator(),
                new BNTPeriodGenerator(),
                new FinalDecisionDateGenerator(),
                new ContinuingUnspecifiedAdjournmentPeriodGenerator(),
                new EventsGenerator(),
            ];
        }

        return [
            new SuspensionPeriodGenerator(),
            new UnspecifiedAdjournmentPeriodGenerator(),
            new ObjectionPeriodGenerator($this->unspecifiedAdjournmentMode),
            new BeslisPeriodGenerator(),
            new IGSPeriodGenerator(),
            new BNTPeriodGenerator(),
            new FinalDecisionDateGenerator(),
            new EventsGenerator(),
        ];
    }
}
