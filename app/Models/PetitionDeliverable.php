<?php

declare(strict_types=1);

namespace App\Models;

use App\Collections\PetitionDeliverableCollection;
use App\Enums\PetitionDeliverableType;
use App\Models\Casts\CalendarDateCast;
use App\Models\Casts\UuidCast;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use App\ValueObjects\CalendarDate;
use Database\Factories\PetitionDeliverableFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface $petition_id
 * @property PetitionDeliverableType $type
 * @property CalendarDate $deadline_at
 * @property string $description
 *
 * @property-read Petition $petition
 */
#[CollectedBy(PetitionDeliverableCollection::class)]
#[UseFactory(PetitionDeliverableFactory::class)]
#[Table('petition_deliverables')]
class PetitionDeliverable extends EloquentModel
{
    /** @use HasFactory<PetitionDeliverableFactory> */
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
            'type' => PetitionDeliverableType::class,
            'deadline_at' => CalendarDateCast::class,
        ];
    }
}
