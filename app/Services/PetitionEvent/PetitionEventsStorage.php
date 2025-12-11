<?php

declare(strict_types=1);

namespace App\Services\PetitionEvent;

use App\Models\Petition;
use App\ValueObjects\WizardEventCollection;
use Illuminate\Session\SessionManager;

use function sprintf;

class PetitionEventsStorage
{
    public function __construct(private readonly SessionManager $manager)
    {
    }

    public function clearWizard(Petition $petition): void
    {
        $this->manager->forget(sprintf('wizard.petition.%s', $petition->id));
    }

    public function setWizardEvents(Petition $petition, WizardEventCollection $events): void
    {
        $this->manager->put(sprintf('wizard.petition.%s.events', $petition->id), $events);
    }

    public function getWizardEvents(Petition $petition): ?WizardEventCollection
    {
        $events = $this->manager->get(sprintf('wizard.petition.%s.events', $petition->id));

        if (!$events instanceof WizardEventCollection) {
            return null;
        }

        return $events;
    }
}
