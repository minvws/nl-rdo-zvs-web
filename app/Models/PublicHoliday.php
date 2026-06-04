<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Casts\CalendarDateCast;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use App\ValueObjects\CalendarDate;
use Database\Factories\PublicHolidayFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Override;

/**
 * @property string $name
 * @property CalendarDate $date
 */
#[UseFactory(PublicHolidayFactory::class)]
#[Table('public_holidays')]
class PublicHoliday extends EloquentModel
{
    use HasId;
    /** @use HasFactory<PublicHolidayFactory> */
    use HasFactory;
    use HasTimestamps;

    #[Override]
    protected function casts(): array
    {
        return [
            'date' => CalendarDateCast::class,
        ];
    }
}
