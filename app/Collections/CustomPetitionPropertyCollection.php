<?php

declare(strict_types=1);

namespace App\Collections;

use App\Models\CustomPetitionProperty;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Collection<array-key, CustomPetitionProperty>
 */
class CustomPetitionPropertyCollection extends Collection
{
    public function toString(): string
    {
        return $this->map(static function (CustomPetitionProperty $customPetitionProperty): string {
            return $customPetitionProperty->name;
        })->implode(', ');
    }
}
