<?php

declare(strict_types=1);

use App\Enums\PetitionTypeType;
use App\Enums\QuerysnapshotType;

return [
    PetitionTypeType::WOO_VERZOEK->value => [
        QuerysnapshotType::DOCUMENT->value,
        QuerysnapshotType::CHAT->value,
    ],
    PetitionTypeType::BEROEP->value => [],
    PetitionTypeType::BEZWAAR->value => [],
];
