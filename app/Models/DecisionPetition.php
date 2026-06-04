<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Casts\UuidCast;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Override;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface $decision_id
 * @property UuidInterface $petition_id
 * @property bool $is_final
 */
#[Table('decision_petition', timestamps: false)]
class DecisionPetition extends Pivot
{
    /**
     * @return array<string, class-string<UuidCast>|string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'decision_id' => UuidCast::class,
            'petition_id' => UuidCast::class,
            'is_final' => 'boolean',
        ];
    }
}
