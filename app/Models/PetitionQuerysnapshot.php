<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuerysnapshotType;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use Database\Factories\PetitionQuerysnapshotFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $petition_id
 * @property string $querysnapshot_id
 * @property QuerysnapshotType $querysnapshot_type
 *
 * @property-read Petition $petition
 */
#[UseFactory(PetitionQuerysnapshotFactory::class)]
class PetitionQuerysnapshot extends EloquentModel
{
    /** @use HasFactory<PetitionQuerysnapshotFactory> */
    use HasFactory;
    use HasId;
    use HasTimestamps;

    protected $table = 'petition_querysnapshots';

    /**
     * @return BelongsTo<Petition, $this>
     */
    public function petition(): BelongsTo
    {
        return $this->belongsTo(Petition::class);
    }

    protected function casts(): array
    {
        return [
            'querysnapshot_type' => QuerysnapshotType::class,
        ];
    }
}
