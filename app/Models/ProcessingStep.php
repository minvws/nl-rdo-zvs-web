<?php

declare(strict_types=1);

namespace App\Models;

use App\Collections\ProcessingStepCollection;
use App\Enums\AssignmentRole;
use App\Enums\ProcessingStepStatus;
use App\Models\Casts\CalendarDateCast;
use App\Models\Casts\UuidCast;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use App\ValueObjects\CalendarDate;
use Database\Factories\ProcessingStepFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Override;
use Ramsey\Uuid\UuidInterface;

/**
 * @property string $name
 * @property int $ordering
 * @property ProcessingStepStatus $status
 * @property UuidInterface $decision_id
 * @property ?CalendarDate $deadline_at
 *
 * @property-read Decision $decision
 * @property-read ?ProcessingStepAssignment $firstAssignee
 * @property-read ?ProcessingStepAssignment $secondAssignee
 */

#[CollectedBy(ProcessingStepCollection::class)]
#[UseFactory(ProcessingStepFactory::class)]
#[Table('processing_steps')]
class ProcessingStep extends EloquentModel
{
    /** @use HasFactory<ProcessingStepFactory> */
    use HasFactory;
    use HasId;
    use HasTimestamps;

    /**
     * @return HasMany<ProcessingStepAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ProcessingStepAssignment::class);
    }

    /**
     * @return HasOne<ProcessingStepAssignment, $this>
     */
    public function firstAssignee(): HasOne
    {
        return $this->hasOne(ProcessingStepAssignment::class)->where('assignment_role', AssignmentRole::PRIMARY);
    }

    /**
     * @return HasOne<ProcessingStepAssignment, $this>
     */
    public function secondAssignee(): HasOne
    {
        return $this->hasOne(ProcessingStepAssignment::class)->where('assignment_role', AssignmentRole::SECONDARY);
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
            'decision_id' => UuidCast::class,
            'ordering' => 'int',
        ];
    }
}
