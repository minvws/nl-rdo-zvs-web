<?php

declare(strict_types=1);

arch('enums namespace should only contain enums')
    ->expect('App\Enums')
    ->toBeEnums();
