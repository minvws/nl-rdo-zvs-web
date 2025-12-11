<?php

declare(strict_types=1);

namespace App\Services\PeriodGenerators;

use App\ValueObjects\EventCalendar;
use App\ValueObjects\PetitionEventData;
use Illuminate\Support\Collection;

interface PeriodGeneratorInterface
{
    /**
     * @param Collection<int, PetitionEventData> $events
     */
    public function generate(Collection $events, EventCalendar $calendar): void;
}
