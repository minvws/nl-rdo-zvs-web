<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\AdjournmentEndReason;
use App\Enums\HearingForm;
use App\Enums\PetitionEventType;
use App\Enums\ResultType;
use App\Enums\SuspensionType;
use App\Exceptions\InvalidPetitionEventData;
use Carbon\CarbonImmutable;

use function array_map;

readonly class PetitionEventData
{
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
        public ?AdjournmentEndReason $adjournmentEndReason = null,
    ) {
        $this->validateDomainRules();
    }

    /**
     * @return array{type: string, date: string, duration: int|null, penalties?: array<int, array{amount: int, duration: int}>, suspension_type?: string|null, result_type?: string|null, hearing_form?: string|null, adjournment_end_reason?: string|null, created_at: CarbonImmutable}
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

        if ($this->adjournmentEndReason instanceof AdjournmentEndReason) {
            $data['adjournment_end_reason'] = $this->adjournmentEndReason->value;
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

        if ($this->adjournmentEndReason instanceof AdjournmentEndReason && !$this->type->hasAdjournmentEndReason()) {
            throw InvalidPetitionEventData::adjournmentEndReasonNotAllowed($this->type);
        }
    }
}
