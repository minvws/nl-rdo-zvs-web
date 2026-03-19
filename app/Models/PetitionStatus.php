<?php

declare(strict_types=1);

namespace App\Models;

use App\Collections\PetitionStatusCollection;
use App\Enums\StatusGroup;
use App\Models\Casts\UuidCast;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use App\QueryBuilders\PetitionStatusQueryBuilder;
use Database\Factories\PetitionStatusFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property string $status
 * @property int $order
 * @property StatusGroup $status_group
 * @property string $bg_color
 */
#[CollectedBy(PetitionStatusCollection::class)]
#[UseEloquentBuilder(PetitionStatusQueryBuilder::class)]
#[UseFactory(PetitionStatusFactory::class)]
class PetitionStatus extends EloquentModel
{
    /** @use HasFactory<PetitionStatusFactory> */
    use HasFactory;
    use HasId;
    use HasTimestamps;

    protected $table = 'petition_statuses';

    /**
     * @return BelongsTo<PetitionType, $this>
     */
    public function petitionType(): BelongsTo
    {
        return $this->belongsTo(PetitionType::class);
    }

    /**
     * @return HasMany<Petition, $this>
     */
    public function petitions(): HasMany
    {
        return $this->hasMany(Petition::class, 'petition_status_id');
    }

    /**
     * @return HasMany<PetitionStatusHistory, $this>
     */
    public function petitionStatusHistories(): HasMany
    {
        return $this->hasMany(PetitionStatusHistory::class, 'petition_status_id');
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'status_group' => StatusGroup::class,
            'petition_type_id' => UuidCast::class,
        ];
    }
}
