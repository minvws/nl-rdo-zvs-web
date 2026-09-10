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
            ->filter(static fn(ProcessingStep $step): bool => $step->deadline_at >= CalendarDate::today())
            ->sortBy(static fn(ProcessingStep $step): ?CalendarDate => $step->deadline_at)
            ->first()?->deadline_at;
    }

    public function countCompleted(): int
    {
        return $this->filter(static fn(ProcessingStep $step): bool => $step->status === ProcessingStepStatus::CLOSED)->count();
    }

    public function countTotal(): int
    {
        return $this->count();
    }

    /**
     * @return array<string>
     */
    public function getInProgressNames(): array
    {
        return $this
            ->filter(static fn(ProcessingStep $step): bool => $step->status === ProcessingStepStatus::PENDING)
            ->sortBy(static fn(ProcessingStep $step): int => $step->ordering)
            ->map(static fn(ProcessingStep $step): string => $step->name)
            ->values()
            ->all();
    }
}
