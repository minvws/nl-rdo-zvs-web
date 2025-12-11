<?php

declare(strict_types=1);

namespace App\Collections;

use App\Models\PetitionStatus;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Collection<int, PetitionStatus>
 */
class PetitionStatusCollection extends Collection
{
    // Collection-specific methods can be added here when needed
}
