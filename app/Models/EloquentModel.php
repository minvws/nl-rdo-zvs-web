<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static Builder|static newModelQuery()
 * @method static Builder|static query()
 */
abstract class EloquentModel extends Model
{
    protected $keyType = 'string';
}
