<?php

declare(strict_types=1);

namespace App\Actions\PetitionCategory;

use App\Models\PetitionCategory;

class UpdatePetitionCategory
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(PetitionCategory $petitionCategory, array $data): PetitionCategory
    {
        $petitionCategory->update($data);

        return $petitionCategory;
    }
}
