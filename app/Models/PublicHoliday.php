<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Casts\CalendarDateCast;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use App\ValueObjects\CalendarDate;
use Database\Factories\PublicHolidayFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string $name
 * @property CalendarDate $date
 */
#[UseFactory(PublicHolidayFactory::class)]
class PublicHoliday extends EloquentModel
{
    use HasId;
    /** @use HasFactory<PublicHolidayFactory> */
    use HasFactory;
    use HasTimestamps;

    protected $table = 'public_holidays';

    protected function casts(): array
    {
        return [
            'date' => CalendarDateCast::class,
        ];
    }
}
