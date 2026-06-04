<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method static TeamQueryBuilder query()
 * @method static TeamQueryBuilder active()
 *
 * @template-extends Builder<Team>
 */
class TeamQueryBuilder extends Builder
{
    public function active(): static
    {
        return $this->where('active', true);
    }
}
