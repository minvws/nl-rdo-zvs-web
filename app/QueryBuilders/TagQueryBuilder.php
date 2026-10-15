<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Models\Department;
use App\Models\Petition;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method static TagQueryBuilder query()
 * @method static TagQueryBuilder active()
 * @method static TagQueryBuilder forDepartment(Department $department)
 * @method static TagQueryBuilder selectableForPetition(Petition $petition)
 *
 * @template-extends Builder<Tag>
 */
class TagQueryBuilder extends Builder
{
    public function active(): static
    {
        return $this->where('active', true);
    }

    public function forDepartment(Department $department): static
    {
        return $this->where('department_id', $department->id);
    }

    /**
     * The tags a case handler may leave on the petition: the active tags of the petition's department, plus the
     * tags of that department already on the petition. An inactive tag can therefore stay, but not be added again.
     */
    public function selectableForPetition(Petition $petition): static
    {
        $this->forDepartment($petition->department)
            ->where(static fn(self $query): self => $query
                ->active()
                ->orWhereHas('petitions', static fn(Builder $petitions): Builder => $petitions->whereKey($petition->id)));

        return $this;
    }
}
