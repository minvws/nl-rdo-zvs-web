<?php

declare(strict_types=1);

namespace App\Actions\Terms;

use App\Models\Petition;

readonly class PetitionDeliverableUpdatePetitionDeadlineAtAction
{
    public function execute(Petition $petition): void
    {
        $petitionDeliverableWithLatestDeadlineAt = $petition->petitionDeliverables
            ->sortBy('deadline_at')
            ->last();

        $petition->deadline_at = $petitionDeliverableWithLatestDeadlineAt !== null
            ? $petitionDeliverableWithLatestDeadlineAt->deadline_at
            : $petition->date_of_entry;

        $petition->save();
    }
}
