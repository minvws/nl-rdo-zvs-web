<?php

declare(strict_types=1);

namespace App\Models;

use App\Collections\TeamCollection;
use App\Models\Concerns\HasDepartment;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use App\Models\Contracts\DepartmentAwareInterface;
use App\QueryBuilders\TeamQueryBuilder;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property string $name
 * @property bool $active
 *
 * @property-read Collection<int, Petition> $petitions
 * @property-read Collection<int, Decision> $decisions
 */
#[CollectedBy(TeamCollection::class)]
#[UseEloquentBuilder(TeamQueryBuilder::class)]
#[UseFactory(TeamFactory::class)]
#[Table('teams')]
class Team extends EloquentModel implements DepartmentAwareInterface
{
     /** @use HasFactory<TeamFactory> */
     use HasFactory;
     use HasId;
     use HasTimestamps;
     use HasDepartment;

     /**
      * @return HasMany<Petition, $this>
      */
    public function petitions(): HasMany
    {
        return $this->hasMany(Petition::class, 'team_id');
    }

     /**
      * @return HasMany<Decision, $this>
      */
    public function decisions(): HasMany
    {
        return $this->hasMany(Decision::class, 'team_id');
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
