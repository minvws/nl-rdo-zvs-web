<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Enums\PetitionEventType;
use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;

use function __;
use function array_map;
use function implode;
use function in_array;

class LastEventMustBeOneOfRule implements ValidationRuleInterface
{
    /**
     * @param array<PetitionEventType> $allowedTypes
     */
    public function __construct(private readonly array $allowedTypes)
    {
    }

    public function validate(PetitionEventData $event, DerivedState $state): ?ValidationResult
    {
        $lastEvent = $state->getEvents()->sortByDesc(
            static function (PetitionEventData $petitionEventData): CarbonImmutable {
                return $petitionEventData->createdAt;
            },
        )->first();

        if ($lastEvent === null) {
            return new ValidationResult([
                'general' => __('term.validation.common.last_event_must_be_one_of', [
                    'events' => $this->formatAllowedTypes(),
                ]),
            ]);
        }

        if (!in_array($lastEvent->type, $this->allowedTypes, true)) {
            return new ValidationResult([
                'general' => __('term.validation.common.last_event_must_be_one_of', [
                    'events' => $this->formatAllowedTypes(),
                ]),
            ]);
        }

        return null;
    }

    private function formatAllowedTypes(): string
    {
        $labels = array_map(static fn(PetitionEventType $type): string => $type->label(), $this->allowedTypes);

        return implode(', ', $labels);
    }
}
