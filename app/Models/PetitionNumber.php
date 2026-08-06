<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Casts\UuidCast;
use Database\Factories\PetitionNumberFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Override;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface $department_id
 * @property int $year
 * @property int $number
 * @property int $id
 */
#[Table('petition_number', timestamps: false)]
#[UseFactory(PetitionNumberFactory::class)]
class PetitionNumber extends EloquentModel
{
    /** @use HasFactory<PetitionNumberFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'department_id' => UuidCast::class,
        ];
    }
}
