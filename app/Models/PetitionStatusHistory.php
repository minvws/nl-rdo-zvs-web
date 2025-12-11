<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasCreatedAt;
use Carbon\CarbonImmutable;
use Database\Factories\PetitionStatusHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $petition_id
 * @property string $petition_status_id
 * @property CarbonImmutable $created_at
 * @property ?string $comment
 *
 * @property-read Petition $petition
 * @property-read PetitionStatus $petitionStatus
 */
#[UseFactory(PetitionStatusHistoryFactory::class)]
class PetitionStatusHistory extends EloquentModel
{
    use HasCreatedAt;
    /** @use HasFactory<PetitionStatusHistoryFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    public $incrementing = false;

    protected $primaryKey;
    protected $table = 'petition_statuses_history_entries';

    /**
     * @return BelongsTo<Petition, $this>
     */
    public function petition(): BelongsTo
    {
        return $this->belongsTo(Petition::class);
    }

    /**
     * @return BelongsTo<PetitionStatus, $this>
     */
    public function petitionStatus(): BelongsTo
    {
        return $this->belongsTo(PetitionStatus::class);
    }
}
