<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PetitionEventType;
use App\Enums\PetitionTypeType;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\WizardEventCollection;

use function array_any;
use function array_filter;
use function array_values;
use function in_array;

class PetitionEventAvailabilityService
{
    private const array MULTIPLE_OCCURRENCE_TYPES = [
        PetitionEventType::LETTER_OF_SUSPENSION_SENT,
        PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
        PetitionEventType::SUSPENSION_END,
    ];

    /**
     * @return array<PetitionEventType>
     */
    public function getAvailableEventTypes(PetitionTypeType $type, WizardEventCollection $currentEvents): array
    {
        if ($type === PetitionTypeType::BEROEP) {
            return [];
        }

        if ($currentEvents->isEmpty()) {
            return $this->getInitialEventForType($type);
        }

        return $this->filterAvailableTypes($type, $currentEvents);
    }

    /**
     * @return array<PetitionEventType>
     */
    private function getInitialEventForType(PetitionTypeType $petitionType): array
    {
        return match ($petitionType) {
            PetitionTypeType::BEZWAAR => [PetitionEventType::PRIMARY_DECISION],
            PetitionTypeType::WOO_VERZOEK => [PetitionEventType::PETITION_RECEIVED],
            default => [],
        };
    }

    /**
     * @return array<PetitionEventType>
     */
    private function filterAvailableTypes(PetitionTypeType $petitionType, WizardEventCollection $currentEvents): array
    {
        $usedTypes = $currentEvents->all()->map(
            static function (PetitionEventData $event) {
                return $event->type->value;
            },
        )->all();

        $allEventTypes = PetitionEventType::cases();

        $availableForType = array_filter(
            $allEventTypes,
            static function (PetitionEventType $eventType) use ($petitionType): bool {
                return $eventType->isAvailableFor($petitionType);
            },
        );

        return array_values(array_filter(
            $availableForType,
            function (PetitionEventType $eventType) use ($petitionType, $usedTypes): bool {
                return $this->canAddEventType($eventType, $petitionType, $usedTypes);
            },
        ));
    }

    /**
     * @param array<string> $usedTypes
     */
    private function canAddEventType(
        PetitionEventType $eventType,
        PetitionTypeType $petitionType,
        array $usedTypes,
    ): bool {
        $conflicts = $eventType->getConflicts($petitionType);

        if ($this->hasConflicts($conflicts, $usedTypes)) {
            return false;
        }

        $dependencies = $eventType->getDependencies($petitionType);

        if (!$this->hasDependencies($dependencies, $usedTypes)) {
            return false;
        }

        if (in_array($eventType, self::MULTIPLE_OCCURRENCE_TYPES, true)) {
            return true;
        }

        return !in_array($eventType->value, $usedTypes, true);
    }

    /**
     * @param array<PetitionEventType> $dependencies
     * @param array<string> $usedTypes
     */
    private function hasDependencies(array $dependencies, array $usedTypes): bool
    {
        if ($dependencies === []) {
            return true;
        }

        return array_any($dependencies, static function ($dependency) use ($usedTypes): bool {
            return in_array($dependency->value, $usedTypes, true);
        });
    }

    /**
     * @param array<PetitionEventType> $conflicts
     * @param array<string> $usedTypes
     */
    private function hasConflicts(array $conflicts, array $usedTypes): bool
    {
        return array_any($conflicts, static function ($conflict) use ($usedTypes): bool {
            return in_array($conflict->value, $usedTypes, true);
        });
    }
}
