<?php

declare(strict_types=1);

namespace App\Models;

use App\Collections\PetitionCategoryCollection;
use App\Models\Concerns\HasDepartment;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use App\Models\Contracts\DepartmentAwareInterface;
use App\QueryBuilders\PetitionCategoryQueryBuilder;
use Database\Factories\PetitionCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property string $name
 * @property bool $active
 */
#[CollectedBy(PetitionCategoryCollection::class)]
#[UseEloquentBuilder(PetitionCategoryQueryBuilder::class)]
#[UseFactory(PetitionCategoryFactory::class)]
#[Table('petition_categories')]
class PetitionCategory extends EloquentModel implements DepartmentAwareInterface
{
    use HasDepartment;
    /** @use HasFactory<PetitionCategoryFactory> */
    use HasFactory;
    use HasId;
    use HasTimestamps;

    /**
     * @return HasMany<Petition, $this>
     */
    public function petitions(): HasMany
    {
        return $this->hasMany(Petition::class, 'petition_category_id');
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('order', static function (Builder $builder): void {
            $builder->orderBy('name');
        });
    }
}
