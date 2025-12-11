<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Authorization\DepartmentRole;
use App\Models\Casts\UuidCast;
use Database\Factories\DepartmentUserFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Ramsey\Uuid\UuidInterface;

/**
 * @property DepartmentRole $role
 * @property UuidInterface $department_id
 * @property UuidInterface $user_id
 */
#[UseFactory(DepartmentUserFactory::class)]
class DepartmentUser extends Pivot
{
    /** @use HasFactory<DepartmentUserFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'department_user';

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'department_id' => UuidCast::class,
            'user_id' => UuidCast::class,
            'role' => DepartmentRole::class,
        ];
    }
}
