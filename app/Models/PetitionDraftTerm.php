<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Casts\CalendarDateCast;
use App\Models\Casts\UuidCast;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use App\ValueObjects\CalendarDate;
use Database\Factories\PetitionDraftTermFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface $id
 * @property UuidInterface $petition_id
 * @property ?string $description
 * @property CalendarDate $start_date
 * @property ?CalendarDate $event_date
 * @property int $days_after_event
 * @property ?CalendarDate $date_withdrawal
 * @property ?int $days_after_date_withdrawal
 *
 * @property-read Petition $petition
 */
#[Table('petition_draft_terms')]
#[UseFactory(PetitionDraftTermFactory::class)]
class PetitionDraftTerm extends EloquentModel
{
    /** @use HasFactory<PetitionDraftTermFactory> */
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

    /**
     * @return array<string, class-string<UuidCast>|class-string<CalendarDateCast>|string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'petition_id' => UuidCast::class,
            'start_date' => CalendarDateCast::class,
            'event_date' => CalendarDateCast::class,
            'date_withdrawal' => CalendarDateCast::class,
            'days_after_event' => 'integer',
            'days_after_date_withdrawal' => 'integer',
        ];
    }
}
