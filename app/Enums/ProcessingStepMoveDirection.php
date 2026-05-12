<?php

declare(strict_types=1);

namespace App\Enums;

enum ProcessingStepMoveDirection: string
{
    case UP = 'up';
    case DOWN = 'down';
}
