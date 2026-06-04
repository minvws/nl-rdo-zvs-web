<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AssignmentRole;
use App\Models\Casts\UuidCast;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use Database\Factories\PetitionAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface $petition_id
 * @property UuidInterface $user_id
 * @property AssignmentRole $assignment_role
 *
 * @property-read Petition $petition
 * @property-read User $user
 */
#[Table('petition_assignments')]
#[UseFactory(PetitionAssignmentFactory::class)]
class PetitionAssignment extends EloquentModel
{
    /** @use HasFactory<PetitionAssignmentFactory> */
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
            'petition_id' => UuidCast::class,
            'user_id' => UuidCast::class,
            'assignment_role' => AssignmentRole::class,
        ];
    }
}
