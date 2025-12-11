<?php

declare(strict_types=1);

namespace App\Enums;

enum CustomCostType: string
{
    case LEGAL_COSTS = 'legal_costs';
    case COURT_FEES = 'court_fees';
    case STATUTORY_INTEREST = 'statutory_interest';
    case SUBSIDY_REPAYMENT = 'subsidy_repayment';
    case OTHER = 'other';
}
