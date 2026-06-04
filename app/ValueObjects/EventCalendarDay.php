<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\SuspensionType;

use function array_merge;
use function collect;
use function get_object_vars;
use function sprintf;

/**
 * @SuppressWarnings(PHPMD)
 */
class EventCalendarDay
{
    private function __construct(
        public readonly CalendarDate $date,
        public readonly ?string $applicableTerm = null,
        public readonly bool $isBudgetDay = false,
        public readonly ?SuspensionType $suspensionType = null,
        public readonly bool $isATW = false,
        public readonly bool $isFirstDayOfBudget = false,
        public readonly bool $isLastDayOfBudget = false,
        public readonly bool $isDeadline = false,
        public readonly int $penaltyTodayInEuros = 0,
        public readonly int $penaltyInEurosForfeited = 0,
        /** @var array<PetitionEventData> */
        public readonly array $petitionEvents = [],
        public readonly bool $isUnspecifiedAdjournment = false,
        public readonly ?string $penaltySourceTerm = null,
        public readonly bool $isUnspecifiedAdjournmentWithdrawal = false,
    ) {
    }

    /**
     * @param array<string, mixed> $properties
     */
    public static function new(CalendarDate $date, array $properties): self
    {
        /** @var array{
         *     applicableTerm?: string|null,
         *     isBudgetDay?: bool,
         *     suspensionType?: SuspensionType|null,
         *     isATW?: bool,
         *     isFirstDayOfBudget?: bool,
         *     isLastDayOfBudget?: bool,
         *     isDeadline?: bool,
         *     penaltyTodayInEuros?: int,
         *     penaltyInEurosForfeited?: int,
         *     petitionEvents?: array<PetitionEventData>,
         *     penaltySourceTerm?: string|null,
         *     isUnspecifiedAdjournmentWithdrawal?: bool,
         * } $typedProperties */
        $typedProperties = $properties;

        return new self($date, ...$typedProperties);
    }

    /**
     * @param array<string, mixed> $properties
     */
    public static function fromProperties(
        EventCalendarDay $existing,
        array $properties,
    ): self {
        $existingProperties = get_object_vars($existing);
        /** @var array{date: CalendarDate} $newProperties */
        $newProperties = array_merge($existingProperties, $properties);

        return new self(...$newProperties);
    }

    public function summary(): string
    {
        $petitionEventValues = collect($this->petitionEvents)
            ->map(static fn (PetitionEventData $event) => $event->type->value)
            ->implode(', ');

        return collect([
            'applicableTerm' => $this->applicableTerm,
            'suspensionType' => $this->suspensionType?->value,
            'isATW' => $this->isATW,
            'isDeadline' => $this->isDeadline,
            'penaltyTodayInEuros' => $this->penaltyTodayInEuros,
            'petitionEvents' => $petitionEventValues ?: null,
            'isUnspecifiedAdjournment' => $this->isUnspecifiedAdjournment,
            'isUnspecifiedAdjournmentWithdrawal' => $this->isUnspecifiedAdjournmentWithdrawal,
        ])
            ->reject(static fn ($value): bool => $value === 0 || ($value === '' || $value === '0') || $value === null)
            ->map(static fn ($value, string $key): string => sprintf('%s: %s', $key, $value))
            ->implode(', ');
    }
}
