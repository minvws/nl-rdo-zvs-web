<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTimestamps;
use Database\Factories\ApiUserFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property string $name
 * @property string $api_key
 * @property string $api_secret
 */
#[Table('api_users')]
#[UseFactory(ApiUserFactory::class)]
class ApiUser extends Authenticatable
{
    /** @use HasFactory<ApiUserFactory> */
    use HasFactory;
    use HasTimestamps;
    use HasApiTokens;
}
