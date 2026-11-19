<?php

declare(strict_types=1);

namespace App\Models;

use App\Collections\PetitionEventCollection;
use App\Enums\HearingForm;
use App\Enums\PetitionEventType;
use App\Enums\ResultType;
use App\Enums\SuspensionType;
use App\Models\Casts\CalendarDateCast;
use App\Models\Casts\UuidCast;
use App\ValueObjects\CalendarDate;
use Carbon\CarbonImmutable;
use Database\Factories\PetitionEventFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\DateFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Override;

use function ksort;

/**
 * @property string $id
 * @property string $petition_id
 * @property PetitionEventType $type
 * @property CalendarDate $date
 * @property ?int $duration
 * @property ?int $withdrawal_duration
 * @property ?SuspensionType $suspension_type
 * @property ?ResultType $result_type
 * @property ?HearingForm $hearing_form
 * @property ?string $reasoning
 * @property array<array{amount: int, duration: int}> $penalties
 * @property CarbonImmutable $created_at
 */
#[CollectedBy(PetitionEventCollection::class)]
#[DateFormat('Y-m-d H:i:s.u')]
class PetitionEvent extends EloquentModel
{
    /** @use HasFactory<PetitionEventFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Petition, $this>
     */
    public function petition(): BelongsTo
    {
        return $this->belongsTo(Petition::class, 'petition_id', 'id', 'petition');
    }

    /**
     * Compares the content of the events, ignoring identity and timestamps, so events can be compared across
     * saves. Newly added attributes are included automatically.
     */
    public function hasSameContentAs(self $other): bool
    {
        return $this->contentAttributes() === $other->contentAttributes();
    }

    /**
     * @return array<mixed>
     */
    private function contentAttributes(): array
    {
        // Raw attributes only hold scalars, so they can be compared strictly (unlike cast values such as CalendarDate).
        $attributes = Arr::except($this->getAttributes(), ['id', 'created_at', 'updated_at']);

        // No penalties can be stored as null or empty list, normalize empty list to null
        $penalties = $this->penalties;
        $attributes['penalties'] = $penalties === [] ? null : $penalties;

        ksort($attributes);

        return $attributes;
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'petition_id' => UuidCast::class,
            'type' => PetitionEventType::class,
            'date' => CalendarDateCast::class,
            'penalties' => 'array',
            'suspension_type' => SuspensionType::class,
            'result_type' => ResultType::class,
            'hearing_form' => HearingForm::class,
        ];
    }
}
