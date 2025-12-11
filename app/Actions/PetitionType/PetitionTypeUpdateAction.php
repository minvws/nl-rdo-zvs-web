<?php

declare(strict_types=1);

namespace App\Actions\PetitionType;

use App\Models\PetitionType;

class PetitionTypeUpdateAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(PetitionType $petitionType, array $data): PetitionType
    {
        $petitionType->update($data);

        return $petitionType;
    }
}
