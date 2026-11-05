<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PetitionEventType;
use App\Enums\PetitionVariant;
use App\Enums\ResultType;
use App\Enums\UnspecifiedAdjournmentMode;
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
        PetitionEventType::REFERRED_TO_DEPARTMENT,
        PetitionEventType::ANSWER_RECEIVED,
    ];

    /**
     * @return array<PetitionEventType>
     */
    public function getAvailableEventTypes(
        PetitionVariant $type,
        WizardEventCollection $currentEvents,
        UnspecifiedAdjournmentMode $adjournmentMode = UnspecifiedAdjournmentMode::FREEZES_TERMS,
    ): array {
        return array_values(array_filter(
            PetitionEventType::cases(),
            fn(PetitionEventType $eventType): bool => $this->isEventTypeAvailable(
                $eventType,
                $type,
                $currentEvents,
                $adjournmentMode,
            ),
        ));
    }

    /**
     * Whether the event type may logically follow the current events
     */
    public function isEventTypeAvailable(
        PetitionEventType $eventType,
        PetitionVariant $type,
        WizardEventCollection $currentEvents,
        UnspecifiedAdjournmentMode $adjournmentMode = UnspecifiedAdjournmentMode::FREEZES_TERMS,
    ): bool {
        if ($type === PetitionVariant::BEROEP) {
            return false;
        }

        if ($currentEvents->isEmpty()) {
            return in_array($eventType, $this->getInitialEventForType($type), true);
        }

        if (!$eventType->isAvailableFor($type)) {
            return false;
        }

        $usedTypes = $currentEvents->all()->map(
            static fn(PetitionEventData $event) => $event->type->value,
        )->all();

        return $this->canAddEventType($eventType, $type, $usedTypes, $currentEvents, $adjournmentMode);
    }

    /**
     * @return array<PetitionEventType>
     */
    private function getInitialEventForType(PetitionVariant $petitionType): array
    {
        return match ($petitionType) {
            PetitionVariant::BEZWAAR => [PetitionEventType::PRIMARY_DECISION],
            PetitionVariant::KLACHT => [PetitionEventType::RECEIPT_OF_COMPLAINT],
            PetitionVariant::WOO_VERZOEK => [PetitionEventType::PETITION_RECEIVED],
            default => [],
        };
    }

    /**
     * @param array<string> $usedTypes
     */
    private function canAddEventType(
        PetitionEventType $eventType,
        PetitionVariant $petitionType,
        array $usedTypes,
        WizardEventCollection $currentEvents,
        UnspecifiedAdjournmentMode $adjournmentMode,
    ): bool {
        $conflicts = $eventType->getConflicts($petitionType);

        if ($this->hasConflicts($conflicts, $usedTypes)) {
            return false;
        }

        if (!$this->passesEventStateRules($eventType, $currentEvents, $adjournmentMode)) {
            return false;
        }

        $dependencies = $eventType->getDependencies($petitionType);

        if (!$this->hasDependencies($dependencies, $usedTypes)) {
            return false;
        }

        if (!$this->passesPrecedingEventRule($eventType, $usedTypes, $currentEvents)) {
            return false;
        }

        return $this->passesOccurrenceRule($eventType, $usedTypes);
    }

    private function passesEventStateRules(
        PetitionEventType $eventType,
        WizardEventCollection $currentEvents,
        UnspecifiedAdjournmentMode $adjournmentMode,
    ): bool {
        return $this->passesOpenStateRules($eventType, $currentEvents)
            && $this->passesDeadlineRules($eventType, $currentEvents, $adjournmentMode)
            && $this->passesResultTypeFollowUpRules($eventType, $currentEvents)
            && $eventType->fitsOpenEvents($currentEvents);
    }

    /**
     * @param array<string> $usedTypes
     */
    private function passesPrecedingEventRule(
        PetitionEventType $eventType,
        array $usedTypes,
        WizardEventCollection $currentEvents,
    ): bool {
        $isRepeat = in_array($eventType->value, $usedTypes, true);
        $requiredLast = $eventType->requiresPrecedingLastEvent($isRepeat);

        return !$requiredLast instanceof PetitionEventType || $currentEvents->last()?->type === $requiredLast;
    }

    /**
     * @param array<string> $usedTypes
     */
    private function passesOccurrenceRule(PetitionEventType $eventType, array $usedTypes): bool
    {
        return in_array($eventType, self::MULTIPLE_OCCURRENCE_TYPES, true)
            || !in_array($eventType->value, $usedTypes, true);
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

    private function passesDeadlineRules(
        PetitionEventType $eventType,
        WizardEventCollection $currentEvents,
        UnspecifiedAdjournmentMode $adjournmentMode,
    ): bool {
        return match ($eventType) {
            PetitionEventType::OPINION_OUTSIDE_TERM => $this->hasExpiredDeadline($currentEvents, $adjournmentMode),
            default => true,
        };
    }

    private function hasExpiredDeadline(
        WizardEventCollection $currentEvents,
        UnspecifiedAdjournmentMode $adjournmentMode,
    ): bool {
        $derivedState = new DerivedState()
            ->withUnspecifiedAdjournmentMode($adjournmentMode)
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
        return $currentEvents->hasOpen(PetitionEventType::LETTER_OF_SUSPENSION_SENT, PetitionEventType::SUSPENSION_END);
    }

    private function hasOpenAdjournment(WizardEventCollection $currentEvents): bool
    {
        return $currentEvents->hasOpen(PetitionEventType::UNSPECIFIED_ADJOURNMENT, PetitionEventType::UNSPECIFIED_ADJOURNMENT_END);
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

        return array_any($dependencies, static fn($dependency): bool => in_array($dependency->value, $usedTypes, true));
    }

    /**
     * @param array<PetitionEventType> $conflicts
     * @param array<string> $usedTypes
     */
    private function hasConflicts(array $conflicts, array $usedTypes): bool
    {
        return array_any($conflicts, static fn(PetitionEventType $conflict): bool => in_array($conflict->value, $usedTypes, true));
    }
}
