<?php

declare(strict_types=1);

namespace App\Enums;

enum ExternalUrlType: string
{
    case PUBLICATION_PAGE = 'publication_page';
    case DECISION_PAGE = 'decision_page';
}
