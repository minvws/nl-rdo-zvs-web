<?php

declare(strict_types=1);

use App\Enums\CustomCostType;
use App\Enums\PetitionVariant;

return [
    PetitionVariant::WOO_VERZOEK->value => [],
    PetitionVariant::BEROEP->value => [
        CustomCostType::LEGAL_COSTS->value,
        CustomCostType::COURT_FEES->value,
        CustomCostType::STATUTORY_INTEREST->value,
        CustomCostType::SUBSIDY_REPAYMENT->value,
        CustomCostType::OTHER->value,
    ],
    PetitionVariant::BEZWAAR->value => [
        CustomCostType::LEGAL_COSTS->value,
        CustomCostType::STATUTORY_INTEREST->value,
        CustomCostType::SUBSIDY_REPAYMENT->value,
        CustomCostType::OTHER->value,
    ],
];
