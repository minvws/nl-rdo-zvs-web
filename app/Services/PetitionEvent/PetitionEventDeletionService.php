<?php

declare(strict_types=1);

namespace App\Services\PetitionEvent;

use App\Enums\PetitionVariant;
use App\Enums\UnspecifiedAdjournmentMode;
use App\Exception\PetitionEventDeletionException;
use App\Models\Petition;
use App\Services\DerivedState;
use App\Services\PetitionEventAvailabilityService;
use App\Validation\PetitionEvent\PetitionEventRules;
use App\ValueObjects\DeletableEvents;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\WizardEventCollection;
use Ramsey\Uuid\UuidInterface;

use function array_all;

/**
 * An event can be deleted from the wizard when the events that remain could have been added in that order, checked
 * with the same availability and validation rules used when adding an event.
 *
 * Whether an event could have been added only depends on the events before it, so deleting an event only affects the
 * events after it.
 */
readonly class PetitionEventDeletionService
{
    public function __construct(
        private PetitionEventAvailabilityService $availabilityService,
        private PetitionEventsStorage $eventsStorage,
    ) {
    }

    /**
     * @throws PetitionEventDeletionException
     */
    public function delete(Petition $petition, UuidInterface $eventId, UnspecifiedAdjournmentMode $adjournmentMode): void
    {
        $events = $this->eventsStorage->getWizardEvents($petition) ?? WizardEventCollection::make();

        // The events may have changed since the page was shown, for instance in another tab.
        $event = $events->find($eventId);
        if (!$event instanceof PetitionEventData) {
            throw PetitionEventDeletionException::eventNotFound($eventId);
        }

        if (!$this->deletableEvents($events, $petition->petitionType->type, $adjournmentMode)->contains($event)) {
            throw PetitionEventDeletionException::cannotBeDeleted($eventId);
        }

        $this->eventsStorage->setWizardEvents($petition, $events->remove($eventId));
    }

    public function deletableEvents(
        WizardEventCollection $events,
        PetitionVariant $petitionType,
        UnspecifiedAdjournmentMode $adjournmentMode,
    ): DeletableEvents {
        // Deleting an event after this one leaves it in place with the same events before it, so it still could not
        // have been added. Only the events up to and including it can be deleted.
        $firstEventThatCouldNotHaveBeenAdded = $this->findFirstEventThatCouldNotHaveBeenAdded($events, $petitionType, $adjournmentMode);

        $candidates = $firstEventThatCouldNotHaveBeenAdded instanceof PetitionEventData
            ? [...$events->before($firstEventThatCouldNotHaveBeenAdded)->all(), $firstEventThatCouldNotHaveBeenAdded]
            : $events->all()->all();

        $deletableEventIds = [];
        foreach ($candidates as $event) {
            if ($this->laterEventsCouldBeAddedWithout($event, $events, $petitionType, $adjournmentMode)) {
                $deletableEventIds[] = $event->id;
            }
        }

        return new DeletableEvents($deletableEventIds);
    }

    private function laterEventsCouldBeAddedWithout(
        PetitionEventData $eventToDelete,
        WizardEventCollection $events,
        PetitionVariant $petitionType,
        UnspecifiedAdjournmentMode $adjournmentMode,
    ): bool {
        $remainingEvents = $events->remove($eventToDelete->id);

        return array_all(
            $events->after($eventToDelete)->all()->all(),
            fn(PetitionEventData $laterEvent): bool => $this->couldHaveBeenAdded(
                $laterEvent,
                $remainingEvents->before($laterEvent),
                $petitionType,
                $adjournmentMode,
            ),
        );
    }

    private function findFirstEventThatCouldNotHaveBeenAdded(
        WizardEventCollection $events,
        PetitionVariant $petitionType,
        UnspecifiedAdjournmentMode $adjournmentMode,
    ): ?PetitionEventData {
        return $events->all()->first(fn(PetitionEventData $event): bool => !$this->couldHaveBeenAdded(
            $event,
            $events->before($event),
            $petitionType,
            $adjournmentMode,
        ));
    }

    private function couldHaveBeenAdded(
        PetitionEventData $event,
        WizardEventCollection $precedingEvents,
        PetitionVariant $petitionType,
        UnspecifiedAdjournmentMode $adjournmentMode,
    ): bool {
        $isAvailable = $this->availabilityService->isEventTypeAvailable($event->type, $petitionType, $precedingEvents, $adjournmentMode);
        if (!$isAvailable) {
            return false;
        }

        $state = new DerivedState()
            ->withUnspecifiedAdjournmentMode($adjournmentMode)
            ->addEvents($precedingEvents->all());

        return PetitionEventRules::validate($event->type, $event, $state)->isValid();
    }
}
