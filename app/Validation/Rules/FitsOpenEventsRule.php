<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Enums\PetitionEventType;
use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\WizardEventCollection;

use function __;

class FitsOpenEventsRule implements ValidationRuleInterface
{
    public function validate(PetitionEventData $event, DerivedState $state): ?ValidationResult
    {
        if ($event->type->fitsOpenEvents(new WizardEventCollection($state->getEvents()))) {
            return null;
        }

        $opening = $event->type->closes();

        if ($opening instanceof PetitionEventType) {
            return new ValidationResult([
                'general' => __('term.validation.common.event_requires_open_dependency', [
                    'event' => $event->type->label(),
                    'required_event' => $opening->label(),
                ]),
            ]);
        }

        return new ValidationResult([
            'general' => __('term.validation.common.event_requires_closing_event', [
                'event' => $event->type->label(),
                'required_event' => $event->type->mustBeClosedBy()?->label(),
            ]),
        ]);
    }
}
