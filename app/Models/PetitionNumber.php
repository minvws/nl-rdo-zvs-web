<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Casts\UuidCast;
use Database\Factories\PetitionNumberFactory;
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
#[UseFactory(PetitionNumberFactory::class)]
class PetitionNumber extends EloquentModel
{
    /** @use HasFactory<PetitionNumberFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'petition_number';

    #[Override]
    protected function casts(): array
    {
        return [
            'department_id' => UuidCast::class,
        ];
    }
}
