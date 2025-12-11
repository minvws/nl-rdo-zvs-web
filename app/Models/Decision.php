<?php

declare(strict_types=1);

namespace App\Models;

use App\Collections\PetitionCollection;
use App\Collections\ProcessingStepCollection;
use App\Enums\DecisionType;
use App\Models\Casts\CalendarDateCast;
use App\Models\Concerns\HasArchivedAt;
use App\Models\Concerns\HasDepartment;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use App\Models\Contracts\DepartmentAwareInterface;
use App\Models\Contracts\TimelineableInterface;
use App\Policies\DecisionPolicy;
use App\QueryBuilders\DecisionQueryBuilder;
use App\ValueObjects\CalendarDate;
use Database\Factories\DecisionFactory;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Ramsey\Uuid\UuidInterface;

/**
 * @property string $name
 * @property ?string $reference
 * @property ?CalendarDate $date
 * @property UuidInterface $department_id
 * @property DecisionType $type
 *
 * @property-read PetitionCollection $petitions
 * @property-read ProcessingStepCollection $processingSteps
 */
#[UseEloquentBuilder(DecisionQueryBuilder::class)]
#[UsePolicy(DecisionPolicy::class)]
class Decision extends EloquentModel implements DepartmentAwareInterface, TimelineableInterface
{
    use HasArchivedAt;
    use HasDepartment;
    /** @use HasFactory<DecisionFactory> */
    use HasFactory;
    use HasId;
    use HasTimestamps;

    protected $table = 'decisions';

    /**
     * @return BelongsToMany<Petition, $this>
     */
    public function petitions(): BelongsToMany
    {
        return $this->belongsToMany(Petition::class, 'decision_petition', 'decision_id', 'petition_id');
    }

    /**
     * @return HasMany<ProcessingStep, $this>
     */
    public function processingSteps(): HasMany
    {
        return $this->hasMany(ProcessingStep::class, 'decision_id')->oldest('deadline_at');
    }

    /**
     * @return MorphMany<TimelineItem, $this>
     */
    public function timelineItems(): MorphMany
    {
        return $this->morphMany(TimelineItem::class, 'timelineable')->latest()->chaperone();
    }

    protected function casts(): array
    {
        return [
            'date' => CalendarDateCast::class,
            'type' => DecisionType::class,
        ];
    }
}
