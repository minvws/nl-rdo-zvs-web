<?php

declare(strict_types=1);

namespace App\Models;

use App\Collections\ProcessingStepCollection;
use App\Enums\ProcessingStepStatus;
use App\Models\Casts\CalendarDateCast;
use App\Models\Casts\UuidCast;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use App\ValueObjects\CalendarDate;
use Database\Factories\ProcessingStepFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Override;
use Ramsey\Uuid\UuidInterface;

/**
 * @property string $name
 * @property int $ordering
 * @property ProcessingStepStatus $status
 * @property UuidInterface $decision_id
 * @property ?CalendarDate $deadline_at
 * @property ?UuidInterface $assigned_to
 *
 * @property-read ?User $assignedUser
 * @property-read Decision $decision
 */

#[CollectedBy(ProcessingStepCollection::class)]
#[UseFactory(ProcessingStepFactory::class)]
class ProcessingStep extends EloquentModel
{
    /** @use HasFactory<ProcessingStepFactory> */
    use HasFactory;
    use HasId;
    use HasTimestamps;

    protected $table = 'processing_steps';

    /**
     * @return HasOne<User, $this>
     */
    public function assignedUser(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'assigned_to');
    }

    /**
     * @return BelongsTo<Decision, $this>
     */
    public function decision(): BelongsTo
    {
        return $this->belongsTo(Decision::class);
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'status' => ProcessingStepStatus::class,
            'deadline_at' => CalendarDateCast::class,
            'assigned_to' => UuidCast::class,
            'decision_id' => UuidCast::class,
            'ordering' => 'int',
        ];
    }
}
