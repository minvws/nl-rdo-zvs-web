<?php

declare(strict_types=1);

namespace App\Enums;

use function __;

enum SuspensionType: string
{
    case SPECIFIED_ADJOURNMENT = 'specified_adjournment';
    case SUSPENSION = 'suspension';
    case SPECIFICATION = 'specification';
    case CONSULTATION = 'consultation';

    public function label(): string
    {
        return match ($this) {
            self::SPECIFIED_ADJOURNMENT => __('suspension_type.specified_adjournment'),
            self::SUSPENSION => __('suspension_type.suspension'),
            self::SPECIFICATION => __('suspension_type.specification'),
            self::CONSULTATION => __('suspension_type.consultation'),
        };
    }

    /**
     * @return array<self>
     */
    public static function getForPetitionType(PetitionVariant $petitionType): array
    {
        return match ($petitionType) {
            PetitionVariant::BEZWAAR => [
                self::SPECIFIED_ADJOURNMENT,
                self::SUSPENSION,
            ],
            PetitionVariant::WOO_VERZOEK => [
                self::SPECIFICATION,
                self::CONSULTATION,
            ],
            default => [],
        };
    }
}
