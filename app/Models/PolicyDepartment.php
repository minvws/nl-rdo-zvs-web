<?php

declare(strict_types=1);

namespace App\Models;

use App\Collections\PolicyDepartmentCollection;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use App\QueryBuilders\PolicyDepartmentQueryBuilder;
use Database\Factories\PolicyDepartmentFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Override;

/**
 * @property string $name
 * @property bool $active
 */
#[CollectedBy(PolicyDepartmentCollection::class)]
#[Table('policy_departments')]
#[UseEloquentBuilder(PolicyDepartmentQueryBuilder::class)]
#[UseFactory(PolicyDepartmentFactory::class)]
class PolicyDepartment extends EloquentModel
{
    /** @use HasFactory<PolicyDepartmentFactory> */
    use HasFactory;
    use HasId;
    use HasTimestamps;

    /**
     * @return BelongsToMany<Petition, $this>
     */
    public function petitions(): BelongsToMany
    {
        return $this->belongsToMany(Petition::class);
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
}
