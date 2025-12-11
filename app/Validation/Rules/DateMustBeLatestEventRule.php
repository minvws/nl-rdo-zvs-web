<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;

use function __;

class DateMustBeLatestEventRule implements ValidationRuleInterface
{
    public function validate(PetitionEventData $event, DerivedState $state): ?ValidationResult
    {
        $lastEvent = $state->getEvents()->sortByDesc(
            static function (PetitionEventData $petitionEventData): CarbonImmutable {
                return $petitionEventData->createdAt;
            },
        )->first();

        if ($lastEvent !== null && $event->date->isBefore($lastEvent->date)) {
            return new ValidationResult([
                'date' => __('term.validation.common.date_must_be_latest_event', [
                    'event' => $event->type->label(),
                ]),
            ]);
        }

        return null;
    }
}
