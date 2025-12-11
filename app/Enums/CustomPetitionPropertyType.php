<?php

declare(strict_types=1);

namespace App\Enums;

enum CustomPetitionPropertyType: string
{
    case NAME = 'name';
    case TITLE = 'title';
    case SUBTITLE = 'subtitle';
    case OPTION = 'option';
    case NO_SELECTED_OPTIONS = 'no-selected-options';
    case NULL = 'null';
}
