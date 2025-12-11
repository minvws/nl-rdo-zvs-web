<?php

declare(strict_types=1);

namespace App\Actions\PetitionEvent;

use App\Models\Petition;
use App\Services\PetitionEvent\PetitionEventsStorage;
use App\ValueObjects\WizardEventCollection;
use Illuminate\Database\DatabaseManager;
use Throwable;

class PetitionEventPersistAction
{
    public function __construct(
        private readonly PetitionEventsStorage $storage,
        private readonly DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function execute(Petition $petition): void
    {
        $events = $this->storage->getWizardEvents($petition) ?? WizardEventCollection::make();

        $this->finishWizard($events, $petition);

        $this->storage->clearWizard($petition);
    }

    /**
     * @throws Throwable
     */
    private function finishWizard(WizardEventCollection $events, Petition $petition): void
    {
        $this->databaseManager->transaction(static function () use ($events, $petition): void {
            $petition->petitionEvents()->delete();

            $petition->petitionEvents()->createMany($events->toArray());
        });
    }
}
