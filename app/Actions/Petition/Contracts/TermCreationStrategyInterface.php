<?php

declare(strict_types=1);

namespace App\Actions\Petition\Contracts;

use App\Models\Petition;
use App\Models\User;

interface TermCreationStrategyInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function createTerms(Petition $petition, array $attributes, User $user): void;
}
