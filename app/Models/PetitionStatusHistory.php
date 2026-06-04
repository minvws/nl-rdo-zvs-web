<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Casts\CalendarDateCast;
use App\Models\Concerns\HasCreatedAt;
use App\Models\Concerns\HasId;
use App\ValueObjects\CalendarDate;
use Carbon\CarbonImmutable;
use Database\Factories\PetitionStatusHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface $id
 * @property string $petition_id
 * @property string $petition_status_id
 * @property CarbonImmutable $created_at
 * @property CalendarDate $date
 * @property ?string $comment
 *
 * @property-read Petition $petition
 * @property-read PetitionStatus $petitionStatus
 */
#[Table('petition_statuses_history_entries')]
#[UseFactory(PetitionStatusHistoryFactory::class)]
class PetitionStatusHistory extends EloquentModel
{
    use HasCreatedAt;
    /** @use HasFactory<PetitionStatusHistoryFactory> */
    use HasFactory;
    use HasId;

    public const UPDATED_AT = null;

    public $incrementing = false;

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

    #[Override]
    protected function casts(): array
    {
        return [
            'date' => CalendarDateCast::class,
        ];
    }
}
