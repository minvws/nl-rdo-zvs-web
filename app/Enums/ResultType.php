<?php

declare(strict_types=1);

namespace App\Enums;

use function __;

enum ResultType: string
{
    case FINAL_DECISION = 'final_decision';
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
     * @return array<self>
     */
    public static function getForPetitionType(PetitionTypeType $petitionType): array
    {
        return match ($petitionType) {
            PetitionTypeType::BEZWAAR => [
                self::FINAL_DECISION,
                self::WITHDRAWN,
                self::FORWARDED,
            ],
            PetitionTypeType::WOO_VERZOEK => [
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
