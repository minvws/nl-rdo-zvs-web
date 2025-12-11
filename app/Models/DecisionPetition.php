<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface $decision_id
 * @property UuidInterface $petition_id
 */
class DecisionPetition extends Pivot
{
    public $timestamps = false;
    protected $table = 'decision_petition';
}
