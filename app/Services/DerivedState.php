<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\Services\PeriodGenerators\BeslisPeriodGenerator;
use App\Services\PeriodGenerators\BNTPeriodGenerator;
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

class DerivedState
{
    /** @var Collection<int, PetitionEventData> */
    private Collection $events;
    private EventCalendar $calendar;

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
            static function (PetitionEventData $event): bool {
                return $event->type === PetitionEventType::RECEIPT_OF_OBJECTION
                    || $event->type === PetitionEventType::PETITION_RECEIVED;
            },
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

        $lastDeadlineDay = $this->calendar
            ->filter(static fn($day): bool => $day->isDeadline)
            ->sortByDesc(static fn($day): string => $day->date->toDateString())
            ->first();

        return $lastDeadlineDay?->date;
    }

    public function deadlineDateForTerm(TermType $term): ?CalendarDate
    {
        $deadlineDay = $this->calendar
            ->filter(static fn($day): bool => $day->isDeadline && $day->applicableTerm === $term->value)
            ->sortByDesc(static fn($day): string => $day->date->toDateString())
            ->first();

        return $deadlineDay?->date;
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
            ->filter(static function (EventCalendarDay $day) use ($date, $term): bool {
                return $day->date->lessThanOrEqualTo($date)
                    && $day->penaltySourceTerm === $term->value;
            })
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
            ->sum(static function (PetitionEventData $event): int {
                return collect($event->penalties)
                    ->sum(static function ($penalty): int {
                        return $penalty->amount * $penalty->duration;
                    });
            });
    }

    /**
     * @return array<int, PeriodGeneratorInterface>
     */
    private function defaultGenerators(): array
    {
        // I think we should provide the generators from the outside
        // maybe resolve them from config
        return [
            new SuspensionPeriodGenerator(),
            new ObjectionPeriodGenerator(),
            new BeslisPeriodGenerator(),
            new IGSPeriodGenerator(),
            new BNTPeriodGenerator(),
            new FinalDecisionDateGenerator(),
            new UnspecifiedAdjournmentPeriodGenerator(),
            new EventsGenerator(),
        ];
    }
}
