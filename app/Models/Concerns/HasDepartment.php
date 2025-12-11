<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Department;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface $department_id
 * @property Department $department
 */
trait HasDepartment
{
    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
