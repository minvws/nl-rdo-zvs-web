<?php

declare(strict_types=1);

namespace App\Collections;

use App\Enums\ProcessingStepStatus;
use App\Models\ProcessingStep;
use App\ValueObjects\CalendarDate;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Collection<array-key, ProcessingStep>
 */
class ProcessingStepCollection extends Collection
{
    public function deadline(): ?CalendarDate
    {
        return $this
            ->filter(static function (ProcessingStep $step): bool {
                return $step->deadline_at >= CalendarDate::today();
            })
            ->sortBy(static function (ProcessingStep $step): ?CalendarDate {
                return $step->deadline_at;
            })
            ->first()?->deadline_at;
    }

    public function countCompleted(): int
    {
        return $this->filter(static function (ProcessingStep $step): bool {
            return $step->status === ProcessingStepStatus::CLOSED;
        })->count();
    }

    public function countTotal(): int
    {
        return $this->count();
    }
}
