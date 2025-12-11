<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Models\PetitionType;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method static PetitionTypeQueryBuilder query()
 * @method static PetitionTypeQueryBuilder active()
 * @method static PetitionTypeQueryBuilder isInUse()
 *
 * @template-extends Builder<PetitionType>
 */
class PetitionTypeQueryBuilder extends Builder
{
    public function active(): static
    {
        return $this->where('active', true);
    }

    public function isInUse(): static
    {
        return $this->has('petitions');
    }
}
