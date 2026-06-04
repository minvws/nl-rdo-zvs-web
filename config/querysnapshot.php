<?php

declare(strict_types=1);

use App\Enums\PetitionVariant;
use App\Enums\QuerysnapshotType;

return [
    PetitionVariant::WOO_VERZOEK->value => [
        QuerysnapshotType::DOCUMENT->value,
        QuerysnapshotType::CHAT->value,
    ],
    PetitionVariant::BEROEP->value => [],
    PetitionVariant::BEZWAAR->value => [],
];
