<?php

declare(strict_types=1);

namespace App\Actions\Petition\Contracts;

use App\Models\Petition;
use App\Models\User;

interface PetitionEventsCreationStrategyInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function create(Petition $petition, array $attributes, User $user): void;
}
