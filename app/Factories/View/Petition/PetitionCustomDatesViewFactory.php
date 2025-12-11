<?php

declare(strict_types=1);

namespace App\Factories\View\Petition;

use App\Collections\PetitionCustomDateCollection;
use App\Models\Petition;
use App\Models\PetitionCustomDate;
use App\Models\PetitionTypeCustomDateLabel;
use App\Services\Petition\PetitionException;

use function assert;

class PetitionCustomDatesViewFactory
{
    /**
     * @throws PetitionException
     */
    public function build(Petition $petition): PetitionCustomDateCollection
    {
        $customDatesForThisPetitionType = PetitionTypeCustomDateLabel::query()->where(
            'petition_type_id',
            $petition->petition_type_id,
        )->get();

        // Ensure the relationship is loaded
        if (!$petition->relationLoaded('customDates')) {
            $petition->load('customDates');
        }

        // Get the loaded custom dates for efficient lookup
        $loadedCustomDates = $petition->getRelation('customDates');
        assert($loadedCustomDates instanceof PetitionCustomDateCollection);

        $petitionCustomDatesCollection = new PetitionCustomDateCollection();
        foreach ($customDatesForThisPetitionType as $customDateForThisPetitionType) {
            $customDateModel = $loadedCustomDates->firstWhere('date_label', $customDateForThisPetitionType->date_label);

            if ($customDateModel instanceof PetitionCustomDate) {
                $petitionCustomDatesCollection->push($customDateModel);
                continue;
            }

            $emptyCustomDate = new PetitionCustomDate([
                'petition_id' => $petition->id,
                'date_label' => $customDateForThisPetitionType->date_label,
                'date' => null,
            ]);
            $petitionCustomDatesCollection->push($emptyCustomDate);
        }

        return $petitionCustomDatesCollection;
    }
}
