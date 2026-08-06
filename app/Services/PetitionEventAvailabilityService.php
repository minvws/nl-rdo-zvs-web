<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PetitionEventType;
use App\Enums\PetitionVariant;
use App\Enums\ResultType;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\WizardEventCollection;

use function array_any;
use function array_filter;
use function array_values;
use function in_array;

readonly class PetitionEventAvailabilityService
{
    private const array MULTIPLE_OCCURRENCE_TYPES = [
        PetitionEventType::LETTER_OF_SUSPENSION_SENT,
        PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
        PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY,
        PetitionEventType::SUSPENSION_END,
        PetitionEventType::UNSPECIFIED_ADJOURNMENT,
        PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
        PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
        PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN,
        PetitionEventType::SENT_PARTIAL_DECISION,
        PetitionEventType::MEETING_SCHEDULED,
        PetitionEventType::HEARING_DATE,
    ];

    /**
     * @return array<PetitionEventType>
     */
    public function getAvailableEventTypes(PetitionVariant $type, WizardEventCollection $currentEvents): array
    {
        if ($type === PetitionVariant::BEROEP) {
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
    private function getInitialEventForType(PetitionVariant $petitionType): array
    {
        return match ($petitionType) {
            PetitionVariant::BEZWAAR => [PetitionEventType::PRIMARY_DECISION],
            PetitionVariant::WOO_VERZOEK => [PetitionEventType::PETITION_RECEIVED],
            default => [],
        };
    }

    /**
     * @return array<PetitionEventType>
     */
    private function filterAvailableTypes(PetitionVariant $petitionType, WizardEventCollection $currentEvents): array
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
            function (PetitionEventType $eventType) use ($petitionType, $usedTypes, $currentEvents): bool {
                return $this->canAddEventType($eventType, $petitionType, $usedTypes, $currentEvents);
            },
        ));
    }

    /**
     * @param array<string> $usedTypes
     */
    private function canAddEventType(
        PetitionEventType $eventType,
        PetitionVariant $petitionType,
        array $usedTypes,
        WizardEventCollection $currentEvents,
    ): bool {
        $conflicts = $eventType->getConflicts($petitionType);

        if ($this->hasConflicts($conflicts, $usedTypes)) {
            return false;
        }

        if (!$this->passesOpenStateRules($eventType, $currentEvents)) {
            return false;
        }

        if (!$this->passesDeadlineRules($eventType, $currentEvents)) {
            return false;
        }

        if (!$this->passesResultTypeFollowUpRules($eventType, $currentEvents)) {
            return false;
        }

        if (
            $eventType === PetitionEventType::APPEAL_DECISION_NOT_TIMELY
            && !$this->hasOpenAppealNotTimelyReceipt($currentEvents)
        ) {
            return false;
        }

        $dependencies = $eventType->getDependencies($petitionType);

        if (!$this->hasDependencies($dependencies, $usedTypes)) {
            return false;
        }

        $isRepeat = in_array($eventType->value, $usedTypes, true);
        $requiredLast = $eventType->requiresPrecedingLastEvent($isRepeat);

        if ($requiredLast instanceof PetitionEventType && $currentEvents->last()?->type !== $requiredLast) {
            return false;
        }

        if (in_array($eventType, self::MULTIPLE_OCCURRENCE_TYPES, true)) {
            return true;
        }

        return !in_array($eventType->value, $usedTypes, true);
    }

    private function hasOpenAppealNotTimelyReceipt(WizardEventCollection $currentEvents): bool
    {
        return $this->countOfType($currentEvents, PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY)
            > $this->countOfType($currentEvents, PetitionEventType::APPEAL_DECISION_NOT_TIMELY);
    }

    /**
     * Enforces result-type-dependent follow-up event visibility:
     *  - ACTUAL_DISCLOSURE / PUBLICATION_DATE are only available for specific FINAL_RESULT result types
     *  - For FINAL_DECISION, PUBLICATION_DATE additionally requires ACTUAL_DISCLOSURE to be present first
     */
    private function passesResultTypeFollowUpRules(PetitionEventType $eventType, WizardEventCollection $currentEvents): bool
    {
        $followUpTypes = [PetitionEventType::ACTUAL_DISCLOSURE, PetitionEventType::PUBLICATION_DATE];

        if (!in_array($eventType, $followUpTypes, true)) {
            return true;
        }

        $finalResultEvent = $currentEvents->all()->first(
            static fn (PetitionEventData $event): bool => $event->type === PetitionEventType::FINAL_RESULT,
        );

        if (!$finalResultEvent instanceof PetitionEventData) {
            return true; // No FINAL_RESULT yet; dependency rules handle availability
        }

        if (!$finalResultEvent->resultType instanceof ResultType) {
            return false; // FINAL_RESULT present but no result type → block follow-ups
        }

        if (!in_array($eventType, $finalResultEvent->resultType->allowedFollowUpEventTypes(), true)) {
            return false;
        }

        // For FINAL_DECISION, PUBLICATION_DATE requires ACTUAL_DISCLOSURE to be present first
        if ($eventType === PetitionEventType::PUBLICATION_DATE && $finalResultEvent->resultType === ResultType::FINAL_DECISION) {
            return $currentEvents->all()->contains(
                static fn (PetitionEventData $event): bool => $event->type === PetitionEventType::ACTUAL_DISCLOSURE,
            );
        }

        return true;
    }

    /**
     * Enforces mutually exclusive open suspension/adjournment state:
     *  - a new suspension or adjournment cannot start while the other is open
     *  - an end event is only available when its own pair is open
     */
    private function passesOpenStateRules(PetitionEventType $eventType, WizardEventCollection $currentEvents): bool
    {
        $suspensionOpen = $this->hasOpenSuspension($currentEvents);
        $adjournmentOpen = $this->hasOpenAdjournment($currentEvents);

        return match ($eventType) {
            PetitionEventType::LETTER_OF_SUSPENSION_SENT => !$adjournmentOpen && !$suspensionOpen,
            PetitionEventType::UNSPECIFIED_ADJOURNMENT => !$adjournmentOpen && !$suspensionOpen,
            PetitionEventType::SUSPENSION_END => $suspensionOpen,
            PetitionEventType::UNSPECIFIED_ADJOURNMENT_END => $adjournmentOpen,
            default => true,
        };
    }

    private function passesDeadlineRules(PetitionEventType $eventType, WizardEventCollection $currentEvents): bool
    {
        $hasExpiredDeadline = $this->hasExpiredDeadline($currentEvents);

        return match ($eventType) {
            PetitionEventType::OPINION_OUTSIDE_TERM => $hasExpiredDeadline,
            default => true,
        };
    }

    private function hasExpiredDeadline(WizardEventCollection $currentEvents): bool
    {
        $derivedState = new DerivedState()
            ->addEvents($currentEvents->all())
            ->buildCalendar();

        $deadline = $derivedState->deadlineDate();
        if (!$deadline instanceof CalendarDate) {
            return false;
        }

        return $deadline->isInThePast();
    }

    private function hasOpenSuspension(WizardEventCollection $currentEvents): bool
    {
        return $this->countOfType($currentEvents, PetitionEventType::LETTER_OF_SUSPENSION_SENT)
            > $this->countOfType($currentEvents, PetitionEventType::SUSPENSION_END);
    }

    private function hasOpenAdjournment(WizardEventCollection $currentEvents): bool
    {
        return $this->countOfType($currentEvents, PetitionEventType::UNSPECIFIED_ADJOURNMENT)
            > $this->countOfType($currentEvents, PetitionEventType::UNSPECIFIED_ADJOURNMENT_END);
    }

    private function countOfType(WizardEventCollection $currentEvents, PetitionEventType $type): int
    {
        return $currentEvents->all()->filter(
            static fn(PetitionEventData $event): bool => $event->type === $type,
        )->count();
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
        return array_any($conflicts, static function (PetitionEventType $conflict) use ($usedTypes): bool {
            return in_array($conflict->value, $usedTypes, true);
        });
    }
}
