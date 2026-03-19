<?php

declare(strict_types=1);

namespace App\Actions\PetitionEvent;

use App\Models\Petition;
use App\Models\User;
use App\Services\PetitionEvent\PetitionEventsStorage;
use App\ValueObjects\WizardEventCollection;
use Throwable;

class PetitionEventPersistAction
{
    public function __construct(
        private readonly PetitionEventsStorage $storage,
        private readonly PetitionEventsPersistenceAction $persistenceAction,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function execute(Petition $petition, User $user): void
    {
        $events = $this->storage->getWizardEvents($petition) ?? WizardEventCollection::make();

        $this->persistenceAction->execute($petition, $events->toArray(), $user);

        $this->storage->clearWizard($petition);
    }
}
