<?php

declare(strict_types=1);

namespace App\Services;

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
