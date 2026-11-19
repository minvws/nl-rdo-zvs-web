<?php

declare(strict_types=1);

namespace App\Services\NotificationRule;

use App\Config\DepartmentConfigurationService;
use App\Enums\PetitionEventType;
use App\Enums\PetitionVariant;
use App\Models\Department;
use App\Models\PetitionType;
use Illuminate\Support\Collection;

use function collect;

/**
 * The event types a department can base its notification rules on: those it can register on its petitions.
 */
readonly class NotificationRuleEventTypes
{
    public function __construct(
        private DepartmentConfigurationService $departmentConfigurationService,
    ) {
    }

    /**
     * @return Collection<string, string> labels keyed by event type value
     */
    public function forDepartment(Department $department): Collection
    {
        $variants = $department->petitionTypes
            ->map(static fn(PetitionType $petitionType): PetitionVariant => $petitionType->type)
            ->unique();
        $labelVariant = $variants->count() === 1 ? $variants->first() : null;

        return collect(PetitionEventType::cases())
            ->filter(fn(PetitionEventType $type): bool => $this->departmentConfigurationService->isEventTypeAvailable($department, $type)
                && $variants->contains(static fn(PetitionVariant $variant): bool => $type->isAvailableFor($variant)))
            ->mapWithKeys(static fn(PetitionEventType $type): array => [$type->value => $type->label($labelVariant)]);
    }
}
