<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasCreatedAt;
use App\Models\Concerns\HasId;
use Database\Factories\PasswordResetTokenFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string $email
 * @property string $token
 */
#[UseFactory(PasswordResetTokenFactory::class)]
class PasswordResetToken extends EloquentModel
{
    use HasCreatedAt;
    /** @use HasFactory<PasswordResetTokenFactory> */
    use HasFactory;
    use HasId;

    public $timestamps = false;
    protected $table = 'password_reset_tokens';
}
