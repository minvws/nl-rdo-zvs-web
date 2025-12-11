<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactRole: string
{
    case APPLICANT = 'applicant';
    case REPRESENTATIVE = 'representative';
    case INSTITUTION = 'institution';
    case STAKEHOLDER = 'stakeholder';
}
