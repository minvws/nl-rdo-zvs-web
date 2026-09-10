<?php

declare(strict_types=1);

namespace App\Factories;

use App\Models\PetitionEvent;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\WizardEventCollection;
use Illuminate\Support\Collection;

final class WizardEventCollectionFactory
{
    /**
     * @param Collection<int, PetitionEvent> $events
     */
    public static function fromModels(Collection $events): WizardEventCollection
    {
        if ($events->isEmpty()) {
            return WizardEventCollection::make();
        }

        $mapped = $events->map(
            static fn(PetitionEvent $event): PetitionEventData => PetitionEventDataFactory::fromModel($event),
        );

        return new WizardEventCollection($mapped);
    }
}
