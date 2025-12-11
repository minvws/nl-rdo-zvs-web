<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Models\PetitionCategory;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method static PetitionCategoryQueryBuilder query()
 * @method static PetitionCategoryQueryBuilder active()
 *
 * @template-extends Builder<PetitionCategory>
 */
class PetitionCategoryQueryBuilder extends Builder
{
    public function active(): static
    {
        return $this->where('active', true);
    }

    protected function isInUse(): static
    {
        return $this->has('petitions');
    }
}
