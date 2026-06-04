<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use Carbon\CarbonImmutable;
use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface $id
 * @property string $name
 * @property string $slug
 * @property string $config_key
 * @property string $abbreviation
 * @property string $hide_column_defaults
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 *
 * @property-read ?DepartmentUser $pivot
 * @property-read Collection<int, UserDepartmentFilter> $departmentFilters
 */
#[UseFactory(DepartmentFactory::class)]
#[Table('departments')]
class Department extends EloquentModel
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;
    use HasId;
    use HasTimestamps;

    #[Override]
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsToMany<User, $this, DepartmentUser>
     */
    public function users(): BelongsToMany
    {
        return $this
            ->belongsToMany(User::class, 'department_user', 'department_id', 'user_id')
            ->withPivot('role')
            ->using(DepartmentUser::class);
    }

    /**
     * @return HasMany<Petition, $this>
     */
    public function petitions(): HasMany
    {
        return $this->hasMany(Petition::class);
    }

    /**
     * @return HasMany<PetitionCategory, $this>
     */
    public function petitionCategories(): HasMany
    {
        return $this->hasMany(PetitionCategory::class);
    }

    /**
     * @return HasMany<PetitionType, $this>
     */
    public function petitionTypes(): HasMany
    {
        return $this->hasMany(PetitionType::class);
    }

    /**
     * @return HasMany<Decision, $this>
     */
    public function decisions(): HasMany
    {
        return $this->hasMany(Decision::class);
    }

    /**
     * @return HasMany<Contact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * @return HasMany<PetitionExport, $this>
     */
    public function petitionExports(): HasMany
    {
        return $this->hasMany(PetitionExport::class);
    }

    /**
     * @return HasMany<UserDepartmentFilter, $this>
     */
    public function departmentFilters(): HasMany
    {
        return $this->hasMany(UserDepartmentFilter::class, 'department_id');
    }
}
