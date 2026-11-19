<?php

declare(strict_types=1);

namespace App\Actions\PetitionEvent;

use App\Models\Petition;
use App\Models\User;
use App\Services\NotificationRule\NotificationRuleEvaluator;
use App\Services\PetitionEvent\PetitionEventsStorage;
use App\ValueObjects\WizardEventCollection;
use Illuminate\Support\Facades\Log;
use Throwable;

use function sprintf;

class PetitionEventPersistAction
{
    public function __construct(
        private readonly PetitionEventsStorage $storage,
        private readonly PetitionEventsPersistenceAction $persistenceAction,
        private readonly NotificationRuleEvaluator $notificationRuleEvaluator,
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

        // Evaluated here rather than in the persistence action, so imports and migrations send no notifications. The
        // events are saved by now, so a failing notification must not fail the save; the daily job tries again.
        try {
            $this->notificationRuleEvaluator->evaluate($petition);
        } catch (Throwable $throwable) {
            Log::error(sprintf('Failed to evaluate notification rules for petition ID: %s', $petition->id), [
                'exception' => $throwable,
            ]);
        }
    }
}
