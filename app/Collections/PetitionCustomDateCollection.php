<?php

declare(strict_types=1);

namespace App\Collections;

use App\Enums\CustomDateLabel;
use App\Models\PetitionCustomDate;
use App\ValueObjects\CalendarDate;
use Illuminate\Database\Eloquent\Collection;
use Webmozart\Assert\Assert;

/**
 * @extends Collection<int, PetitionCustomDate>
 */
class PetitionCustomDateCollection extends Collection
{
    /**
     * Get the maximum date from custom dates that affect the date of close
     */
    public function getMaxDateForDateOfClose(): ?CalendarDate
    {
        $maxDate = $this->filter(static function (PetitionCustomDate $petitionCustomDate) {
            return $petitionCustomDate->date_label->isAffectingDateOfClose();
        })->max('date');

        Assert::nullOrIsInstanceOf($maxDate, CalendarDate::class);

        return $maxDate;
    }

    /**
     * Get a custom date by its label
     */
    public function getByLabel(CustomDateLabel $label): ?PetitionCustomDate
    {
        return $this->firstWhere('date_label', $label);
    }
}
