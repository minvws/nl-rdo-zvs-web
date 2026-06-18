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
    case PARTIAL_DECISION = 'partial_decision';
    case WITHDRAWN = 'withdrawn';
    case FORWARDED = 'forwarded';
    case REJECTED = 'rejected';
    case DISMISSED = 'dismissed';
    case RECONSIDERED = 'reconsidered';
    case ALREADY_PUBLIC = 'already_public';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::FINAL_DECISION => __('result_type.final_decision'),
            self::PARTIAL_DECISION => __('result_type.partial_decision'),
            self::WITHDRAWN => __('result_type.withdrawn'),
            self::FORWARDED => __('result_type.forwarded'),
            self::REJECTED => __('result_type.rejected'),
            self::DISMISSED => __('result_type.dismissed'),
            self::RECONSIDERED => __('result_type.reconsidered'),
            self::ALREADY_PUBLIC => __('result_type.already_public'),
            self::OTHER => __('result_type.other'),
        };
    }

    /**
     * @return array<string, array<self>>
     */
    public static function getGroupedForPetitionType(PetitionVariant $petitionType): array
    {
        $all = self::getForPetitionType($petitionType);
        $withDecision = [self::FINAL_DECISION, self::PARTIAL_DECISION, self::REJECTED, self::DISMISSED];

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
