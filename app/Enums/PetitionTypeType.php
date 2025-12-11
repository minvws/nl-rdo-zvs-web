<?php

declare(strict_types=1);

namespace App\Enums;

enum PetitionTypeType: string
{
    case BEROEP = 'beroep';
    case BEZWAAR = 'bezwaar';
    case WOO_VERZOEK = 'woo_verzoek';
}
