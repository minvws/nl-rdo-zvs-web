<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @method static Builder|static newModelQuery()
 * @method static Builder|static query()
 */
abstract class EloquentModel extends Model
{
    #[Override]
    protected $keyType = 'string';
}
