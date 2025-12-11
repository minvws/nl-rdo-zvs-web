<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExportType;
use App\Models\Casts\CalendarDateCast;
use App\Models\Casts\UuidCast;
use App\Models\Concerns\HasDepartment;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use App\Models\Contracts\DepartmentAwareInterface;
use App\Policies\PetitionExportPolicy;
use App\ValueObjects\CalendarDate;
use Carbon\CarbonImmutable;
use Database\Factories\PetitionExportFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property PetitionType $petitionType
 * @property ?PetitionCategory $petitionCategory
 * @property CalendarDate $date_from
 * @property CalendarDate $date_to
 * @property string $filters
 * @property ExportType $type
 * @property string $disk
 * @property string $path
 * @property CarbonImmutable $created_at
 *
 * @property-read Department $department
 */
#[UseFactory(PetitionExportFactory::class)]
#[UsePolicy(PetitionExportPolicy::class)]
class PetitionExport extends EloquentModel implements DepartmentAwareInterface
{
    use HasDepartment;
    /** @use HasFactory<PetitionExportFactory> */
    use HasFactory;
    use HasId;
    use HasTimestamps;

    protected $table = 'petition_exports';

    /**
     * @return HasOne<PetitionType, $this>
     */
    public function petitionType(): HasOne
    {
        return $this->hasOne(PetitionType::class, 'id', 'petition_type_id');
    }

    /**
     * @return HasOne<PetitionCategory, $this>
     */
    public function petitionCategory(): HasOne
    {
        return $this->hasOne(PetitionCategory::class, 'id', 'petition_category_id');
    }

    protected function casts(): array
    {
        return [
            'department_id' => UuidCast::class,
            'petition_type_id' => UuidCast::class,
            'petition_category_id' => UuidCast::class,
            'date_from' => CalendarDateCast::class,
            'date_to' => CalendarDateCast::class,
            'type' => ExportType::class,
        ];
    }
}
