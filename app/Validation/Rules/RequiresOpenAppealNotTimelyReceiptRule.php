<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Enums\PetitionEventType;
use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\ValueObjects\PetitionEventData;

use function __;

class RequiresOpenAppealNotTimelyReceiptRule implements ValidationRuleInterface
{
    public function validate(PetitionEventData $event, DerivedState $state): ?ValidationResult
    {
        $receiptCount = $state->getEvents()->filter(
            static fn(PetitionEventData $petitionEventData): bool =>
                $petitionEventData->type === PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY,
        )->count();

        $decisionCount = $state->getEvents()->filter(
            static fn(PetitionEventData $petitionEventData): bool =>
                $petitionEventData->type === PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
        )->count();

        if ($receiptCount > $decisionCount) {
            return null;
        }

        return new ValidationResult([
            'general' => __('term.validation.common.event_requires_open_dependency', [
                'event' => $event->type->label(),
                'required_event' => PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY->label(),
            ]),
        ]);
    }
}
