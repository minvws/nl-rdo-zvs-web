<?php

declare(strict_types=1);

namespace App\Actions\PetitionEvent\Contracts;

use App\Models\Petition;

interface UpdatePetitionTotalsFromTermsActionInterface
{
    public function execute(Petition $petition): void;
}
