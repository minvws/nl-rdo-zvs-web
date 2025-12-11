<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Authorization\GlobalRole;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use Database\Factories\UserGlobalRoleFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property GlobalRole $role
 */
#[UseFactory(UserGlobalRoleFactory::class)]
class UserGlobalRole extends EloquentModel
{
    /** @use HasFactory<UserGlobalRoleFactory> */
    use HasFactory;
    use HasId;
    use HasTimestamps;

    protected $table = 'user_global_roles';

    protected function casts(): array
    {
        return [
            'role' => GlobalRole::class,
        ];
    }
}
