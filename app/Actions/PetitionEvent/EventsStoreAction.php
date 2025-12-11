<?php

declare(strict_types=1);

namespace App\Actions\PetitionEvent;

use App\Models\Petition;
use App\Services\PetitionEvent\PetitionEventsStorage;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\WizardEventCollection;

class EventsStoreAction
{
    public function __construct(
        private readonly PetitionEventsStorage $storage,
    ) {
    }

    public function execute(Petition $petition, PetitionEventData $petitionEventData): void
    {
        $events = $this->storage->getWizardEvents($petition) ?? WizardEventCollection::make();

        $events = $events->add($petitionEventData);

        $this->storage->setWizardEvents($petition, $events);
    }
}
