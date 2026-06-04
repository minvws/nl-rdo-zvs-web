<?php

declare(strict_types=1);

namespace App\Models;

use App\Collections\PetitionTypeCollection;
use App\Enums\PetitionVariant;
use App\Models\Concerns\HasDepartment;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use App\Models\Contracts\DepartmentAwareInterface;
use App\QueryBuilders\PetitionTypeQueryBuilder;
use Database\Factories\PetitionTypeFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property string $name
 * @property string $particularity_label
 * @property PetitionVariant $type
 * @property bool $active
 */
#[CollectedBy(PetitionTypeCollection::class)]
#[UseEloquentBuilder(PetitionTypeQueryBuilder::class)]
#[UseFactory(PetitionTypeFactory::class)]
#[Table('petition_types')]
class PetitionType extends EloquentModel implements DepartmentAwareInterface
{
    use HasDepartment;
    /** @use HasFactory<PetitionTypeFactory> */
    use HasFactory;
    use HasId;
    use HasTimestamps;

    /**
     * @return HasMany<PetitionTypeCustomDateLabel, $this>
     */
    public function customDateLabels(): HasMany
    {
        return $this->hasMany(PetitionTypeCustomDateLabel::class);
    }

    /**
     * @return HasMany<Petition, $this>
     */
    public function petitions(): HasMany
    {
        return $this->hasMany(Petition::class, 'petition_type_id');
    }

    /**
     * @return array<string, class-string<PetitionVariant>|string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'type' => PetitionVariant::class,
            'active' => 'boolean',
        ];
    }
}
