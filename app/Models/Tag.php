<?php

declare(strict_types=1);

namespace App\Models;

use App\Collections\PetitionCollection;
use App\Collections\TagCollection;
use App\Enums\TagColor;
use App\Models\Concerns\HasDepartment;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use App\Models\Contracts\DepartmentAwareInterface;
use App\QueryBuilders\TagQueryBuilder;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Override;

/**
 * @property string $name
 * @property TagColor $color
 * @property ?string $description
 * @property bool $active
 *
 * @property-read int $petitions_count
 * @property-read PetitionCollection $petitions
 */
#[CollectedBy(TagCollection::class)]
#[Table('tags')]
#[UseEloquentBuilder(TagQueryBuilder::class)]
#[UseFactory(TagFactory::class)]
class Tag extends EloquentModel implements DepartmentAwareInterface
{
    use HasDepartment;
    /** @use HasFactory<TagFactory> */
    use HasFactory;
    use HasId;
    use HasTimestamps;

    /**
     * @return BelongsToMany<Petition, $this>
     */
    public function petitions(): BelongsToMany
    {
        return $this->belongsToMany(Petition::class, 'petition_tag', 'tag_id', 'petition_id');
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'color' => TagColor::class,
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
