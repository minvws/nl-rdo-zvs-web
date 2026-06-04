<?php

declare(strict_types=1);

namespace App\Models;

use App\Collections\PetitionTermCollection;
use App\Enums\TermType;
use App\Models\Casts\CalendarDateCast;
use App\Models\Casts\UuidCast;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use App\Services\Terms\TermDateCalculator;
use App\ValueObjects\CalendarDate;
use Database\Factories\PetitionTermFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface $id
 * @property UuidInterface $petition_id
 * @property TermType $type
 * @property string|null $description
 * @property int $duration_in_days
 * @property int $penalty_amount_in_euros
 * @property CalendarDate $start_date
 * @property CalendarDate $end_date
 * @property ?UuidInterface $parent_id
 *
 * @property-read Petition $petition
 */
#[CollectedBy(PetitionTermCollection::class)]
#[UseFactory(PetitionTermFactory::class)]
#[Table('petition_terms')]
class PetitionTerm extends EloquentModel
{
    /** @use HasFactory<PetitionTermFactory> */
    use HasFactory;
    use HasId;
    use HasTimestamps;

    /**
     * @return BelongsTo<Petition, $this>
     */
    public function petition(): BelongsTo
    {
        return $this->belongsTo(Petition::class);
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'petition_id' => UuidCast::class,
            'type' => TermType::class,
            'start_date' => CalendarDateCast::class,
            'end_date' => CalendarDateCast::class,
            'parent_id' => UuidCast::class,
        ];
    }

    #[Override]
    protected static function booted(): void
    {
        static::creating(static function (PetitionTerm $model): void {
            $model->end_date = TermDateCalculator::calculateEndDate($model->start_date, $model->duration_in_days);
        });
    }
}
