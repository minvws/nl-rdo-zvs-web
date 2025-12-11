<?php

declare(strict_types=1);

namespace App\Models;

use App\Collections\PetitionTypeCustomDateLabelCollection;
use App\Enums\CustomDateLabel;
use App\Models\Casts\UuidCast;
use Database\Factories\PetitionTypeCustomDateLabelFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface $petition_type_id
 * @property CustomDateLabel $date_label
 */
#[CollectedBy(PetitionTypeCustomDateLabelCollection::class)]
#[UseFactory(PetitionTypeCustomDateLabelFactory::class)]
class PetitionTypeCustomDateLabel extends EloquentModel
{
    /** @use HasFactory<PetitionTypeCustomDateLabelFactory> */
    use HasFactory;

    public $primaryKey = 'internal_id';
    protected $table = 'petition_type_custom_dates_labels';

    /**
     * @return BelongsTo<PetitionType, $this>
     */
    public function petitionType(): BelongsTo
    {
        return $this->belongsTo(PetitionType::class);
    }

    protected function casts(): array
    {
        return [
            'petition_type_id' => UuidCast::class,
            'date_label' => CustomDateLabel::class,
        ];
    }
}
