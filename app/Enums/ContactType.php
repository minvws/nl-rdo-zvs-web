<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactType: string
{
    case CIVILIAN = 'burger';
    case MEDIA = 'media';
    case COMPANY = 'bedrijf';
    case REPRESENTATIVE = 'belangenbehartiger';
    case LEGAL_SPECIALIST = 'juridisch_specialist';
    case OTHER = 'anders';
    case INSTITUTION = 'instelling';
}
