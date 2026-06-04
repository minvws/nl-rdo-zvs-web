<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Authorization\DepartmentRole;
use App\Models\Casts\UuidCast;
use Database\Factories\DepartmentUserFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Override;
use Ramsey\Uuid\UuidInterface;

/**
 * @property DepartmentRole $role
 * @property UuidInterface $department_id
 * @property UuidInterface $user_id
 */
#[UseFactory(DepartmentUserFactory::class)]
#[Table('department_user', timestamps: false)]
class DepartmentUser extends Pivot
{
    /** @use HasFactory<DepartmentUserFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    #[Override]
    public function casts(): array
    {
        return [
            'department_id' => UuidCast::class,
            'user_id' => UuidCast::class,
            'role' => DepartmentRole::class,
        ];
    }
}
