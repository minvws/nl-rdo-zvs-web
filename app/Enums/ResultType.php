<?php

declare(strict_types=1);

namespace App\Enums;

use function __;
use function array_filter;
use function array_values;
use function in_array;

enum ResultType: string
{
    case FINAL_DECISION = 'final_decision';
    case FINAL_DECISION_55_REQUEST = 'final_decision_55_request';
    case WITHDRAWN = 'withdrawn';
    case FORWARDED = 'forwarded';
    case REJECTED = 'rejected';
    case DISMISSED = 'dismissed';
    case RECONSIDERED = 'reconsidered';
    case ALREADY_PUBLIC = 'already_public';
    case OTHER = 'other';

    /**
     * Returns the PetitionEventTypes that may be added after a FINAL_RESULT with this result type.
     * An empty array means no follow-up tiles should be shown.
     *
     * @return array<PetitionEventType>
     */
    public function allowedFollowUpEventTypes(): array
    {
        return match ($this) {
            self::FINAL_DECISION => [PetitionEventType::ACTUAL_DISCLOSURE, PetitionEventType::PUBLICATION_DATE],
            self::FINAL_DECISION_55_REQUEST => [PetitionEventType::ACTUAL_DISCLOSURE],
            self::REJECTED => [PetitionEventType::PUBLICATION_DATE],
            default => [],
        };
    }

    public function requiresReasoning(): bool
    {
        return match ($this) {
            self::OTHER => true,
            default => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::FINAL_DECISION => __('result_type.default.final_decision'),
            self::FINAL_DECISION_55_REQUEST => __('result_type.default.final_decision_55_request'),
            self::WITHDRAWN => __('result_type.default.withdrawn'),
            self::FORWARDED => __('result_type.default.forwarded'),
            self::REJECTED => __('result_type.default.rejected'),
            self::DISMISSED => __('result_type.default.dismissed'),
            self::RECONSIDERED => __('result_type.default.reconsidered'),
            self::ALREADY_PUBLIC => __('result_type.default.already_public'),
            self::OTHER => __('result_type.default.other'),
        };
    }

    /**
     * @return array<string, array<self>>
     */
    public static function getGroupedForPetitionType(PetitionVariant $petitionType): array
    {
        $all = self::getForPetitionType($petitionType);
        $withDecision = [self::FINAL_DECISION, self::FINAL_DECISION_55_REQUEST, self::REJECTED, self::DISMISSED];

        return [
            'with' => array_values(array_filter(
                $all,
                static fn (self $type): bool => in_array($type, $withDecision, true),
            )),
            'without' => array_values(array_filter(
                $all,
                static fn (self $type): bool => !in_array($type, $withDecision, true),
            )),
        ];
    }

    /**
     * @return array<self>
     */
    public static function getForPetitionType(PetitionVariant $petitionType): array
    {
        return match ($petitionType) {
            PetitionVariant::BEZWAAR => [
                self::FINAL_DECISION,
                self::WITHDRAWN,
                self::FORWARDED,
            ],
            PetitionVariant::WOO_VERZOEK => [
                self::FINAL_DECISION,
                self::FINAL_DECISION_55_REQUEST,
                self::WITHDRAWN,
                self::FORWARDED,
                self::REJECTED,
                self::DISMISSED,
                self::RECONSIDERED,
                self::ALREADY_PUBLIC,
                self::OTHER,
            ],
            default => [],
        };
    }
}
