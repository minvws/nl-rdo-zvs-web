<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Casts\UuidCast;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;
use Ramsey\Uuid\UuidInterface;

/**
 * @property Department $department
 * @property User $user
 * @property UuidInterface $user_id
 * @property UuidInterface $id
 * @property UuidInterface $department_id
 * @property string $filterable_type
 * @property array<string, mixed> $filter_data
 */
final class UserDepartmentFilter extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'department_id',
        'filterable_type',
        'filter_data',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return array<string, class-string<UuidCast>|string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'filter_data' => 'array',
            'user_id' => UuidCast::class,
            'department_id' => UuidCast::class,
        ];
    }
}
