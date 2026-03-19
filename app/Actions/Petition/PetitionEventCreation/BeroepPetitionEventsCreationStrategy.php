<?php

declare(strict_types=1);

namespace App\Actions\Petition\PetitionEventCreation;

use App\Actions\Petition\Contracts\PetitionEventsCreationStrategyInterface;
use App\Models\Petition;
use App\Models\User;

readonly class BeroepPetitionEventsCreationStrategy implements PetitionEventsCreationStrategyInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function create(Petition $petition, array $attributes, User $user): void
    {
        // No implementation needed
    }
}
