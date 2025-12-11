<?php

declare(strict_types=1);

namespace App\Models;

use App\Collections\PetitionCustomDateCollection;
use App\Enums\CustomDateLabel;
use App\Models\Casts\CalendarDateCast;
use App\Models\Casts\UuidCast;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use App\ValueObjects\CalendarDate;
use Database\Factories\PetitionCustomDateFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface $id
 * @property UuidInterface $petition_id
 * @property CustomDateLabel $date_label
 * @property ?CalendarDate $date
 *
 * @property-read Petition $petition
 */
#[CollectedBy(PetitionCustomDateCollection::class)]
#[UseFactory(PetitionCustomDateFactory::class)]
class PetitionCustomDate extends EloquentModel
{
    /** @use HasFactory<PetitionCustomDateFactory> */
    use HasFactory;
    use HasId;
    use HasTimestamps;

    protected $table = 'petition_custom_dates';

    protected $fillable = [
        'petition_id',
        'date_label',
        'date',
    ];

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
            'id' => UuidCast::class,
            'petition_id' => UuidCast::class,
            'date_label' => CustomDateLabel::class,
            'date' => CalendarDateCast::class,
        ];
    }
}
