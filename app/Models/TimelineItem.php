<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TimelineType;
use App\Models\Casts\UuidCast;
use App\Models\Concerns\HasTimestamps;
use Database\Factories\TimelineItemFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Override;
use Ramsey\Uuid\UuidInterface;

/**
 * @property int $internal_id
 * @property UuidInterface $petition_id
 * @property ?UuidInterface $user_id
 * @property ArrayObject<string, covariant mixed> $data
 * @property TimelineType $type
 *
 * @property-read Model $timelineable
 * @property-read User $user
 */
#[Table('timeline_items', key: 'internal_id')]
#[UseFactory(TimelineItemFactory::class)]
class TimelineItem extends EloquentModel
{
    /** @use HasFactory<TimelineItemFactory> */
    use HasFactory;
    use HasTimestamps;

    /**
     * @return MorphTo<Model, $this>
     */
    public function timelineable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'timelineable_type', 'timelineable_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'petition_id' => UuidCast::class,
            'user_id' => UuidCast::class,
            'data' => AsArrayObject::class,
            'type' => TimelineType::class,
        ];
    }
}
