<?php

declare(strict_types=1);

namespace App\Collections;

use App\Models\Petition;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Collection<array-key, Petition>
 */
class PetitionCollection extends Collection
{
    public function toString(): string
    {
        return $this->map(static function (Petition $petition): string {
            return $petition->number;
        })->join(', ');
    }
}
