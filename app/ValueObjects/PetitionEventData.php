<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\HearingForm;
use App\Enums\PetitionEventType;
use App\Enums\ResultType;
use App\Enums\SuspensionType;
use App\Exceptions\InvalidPetitionEventData;
use Carbon\CarbonImmutable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

use function array_map;

readonly class PetitionEventData
{
    /**
     * Identifies the event within the wizard, the events are only stored in the database when the wizard is finished.
     */
    public UuidInterface $id;

    /**
     * @param array<int, PenaltyData> $penalties
     */
    public function __construct(
        public PetitionEventType $type,
        public CalendarDate $date,
        public CarbonImmutable $createdAt,
        public ?int $duration = null,
        public array $penalties = [],
        public ?SuspensionType $suspensionType = null,
        public ?ResultType $resultType = null,
        public ?HearingForm $hearingForm = null,
        public ?string $reasoning = null,
        public ?int $withdrawalDuration = null,
        ?UuidInterface $id = null,
    ) {
        $this->id = $id ?? Uuid::uuid7();

        $this->validateDomainRules();
    }

    /**
     * @return array{type: string, date: string, duration: int|null, penalties?: array<int, array{amount: int, duration: int}>, suspension_type?: string|null, result_type?: string|null, hearing_form?: string|null, reasoning?: string|null, withdrawal_duration?: int|null, created_at: CarbonImmutable}
     */
    public function toArray(): array
    {
        $data = [
            'type' => $this->type->value,
            'date' => $this->date->toDateString(),
            'duration' => $this->duration,
        ];

        if ($this->penalties !== []) {
            $data['penalties'] = array_map(
                static fn(PenaltyData $penalty): array => $penalty->toArray(),
                $this->penalties,
            );
        }

        if ($this->suspensionType instanceof SuspensionType) {
            $data['suspension_type'] = $this->suspensionType->value;
        }

        if ($this->resultType instanceof ResultType) {
            $data['result_type'] = $this->resultType->value;
        }

        if ($this->hearingForm instanceof HearingForm) {
            $data['hearing_form'] = $this->hearingForm->value;
        }

        if ($this->reasoning !== null) {
            $data['reasoning'] = $this->reasoning;
        }

        if ($this->withdrawalDuration !== null) {
            $data['withdrawal_duration'] = $this->withdrawalDuration;
        }

        $data['created_at'] = $this->createdAt;

        return $data;
    }

    private function validateDomainRules(): void
    {
        if ($this->suspensionType instanceof SuspensionType && !$this->type->hasSuspensionType()) {
            throw InvalidPetitionEventData::suspensionTypeNotAllowed($this->type);
        }

        if ($this->resultType instanceof ResultType && !$this->type->hasResultType()) {
            throw InvalidPetitionEventData::resultTypeNotAllowed($this->type);
        }

        if ($this->hearingForm instanceof HearingForm && !$this->type->hasHearingForm()) {
            throw InvalidPetitionEventData::hearingFormNotAllowed($this->type);
        }

        if ($this->reasoning !== null && !$this->type->hasReasoning()) {
            throw InvalidPetitionEventData::reasoningNotAllowed($this->type);
        }

        if ($this->type->requiresReasoning() && $this->reasoning === null) {
            throw InvalidPetitionEventData::reasoningRequired($this->type);
        }

        if ($this->withdrawalDuration !== null && !$this->type->hasWithdrawalDuration()) {
            throw InvalidPetitionEventData::withdrawalDurationNotAllowed($this->type);
        }
    }
}
