<?php

declare(strict_types=1);

namespace App\Actions\Petition\TermCreation;

use App\Actions\Petition\Contracts\TermCreationStrategyInterface;
use App\Models\Petition;
use App\Models\User;

readonly class BeroepTermCreationStrategy implements TermCreationStrategyInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function createTerms(Petition $petition, array $attributes, User $user): void
    {
    }
}
