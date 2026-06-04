<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AssignmentRole;
use App\Models\Casts\UuidCast;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use Database\Factories\ProcessingStepAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface $processing_step_id
 * @property UuidInterface $user_id
 * @property AssignmentRole $assignment_role
 *
 * @property-read ProcessingStep $processingStep
 * @property-read User $user
 */
#[Table('processing_step_assignments')]
#[UseFactory(ProcessingStepAssignmentFactory::class)]
class ProcessingStepAssignment extends EloquentModel
{
    /** @use HasFactory<ProcessingStepAssignmentFactory> */
    use HasFactory;
    use HasId;
    use HasTimestamps;

    /**
     * @return BelongsTo<ProcessingStep, $this>
     */
    public function processingStep(): BelongsTo
    {
        return $this->belongsTo(ProcessingStep::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'processing_step_id' => UuidCast::class,
            'user_id' => UuidCast::class,
            'assignment_role' => AssignmentRole::class,
        ];
    }
}
