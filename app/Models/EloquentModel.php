<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Override;

abstract class EloquentModel extends Model
{
    #[Override]
    protected $keyType = 'string';
}
