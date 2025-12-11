<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Models\Decision;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method static DecisionQueryBuilder query()
 *
 * @template-extends Builder<Decision>
 */
class DecisionQueryBuilder extends Builder
{
    public function notArchived(): DecisionQueryBuilder
    {
        return $this->whereNull('archived_at');
    }
}
