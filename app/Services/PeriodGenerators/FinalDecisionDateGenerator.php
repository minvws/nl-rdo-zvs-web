<?php

declare(strict_types=1);

namespace App\Services\PeriodGenerators;

use App\Enums\PetitionEventType;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\PetitionEventData;
use Illuminate\Support\Collection;

class FinalDecisionDateGenerator implements PeriodGeneratorInterface
{
    /**
     * @param Collection<int, PetitionEventData> $events
     */
    public function generate(Collection $events, EventCalendar $calendar): void
    {
        $finalDecisionEvent = $events->first(
            static function (PetitionEventData $petitionEventData): bool {
                return $petitionEventData->type === PetitionEventType::FINAL_RESULT;
            },
        );

        if (!$finalDecisionEvent) {
            return;
        }

        $finalDate = $finalDecisionEvent->date;

        $calendar->removeAllDaysAfterDate($finalDate);
    }
}
