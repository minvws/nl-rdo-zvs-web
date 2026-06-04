<?php

declare(strict_types=1);

use App\Enums\ExternalUrlType;
use App\Enums\PetitionVariant;

return [
    PetitionVariant::WOO_VERZOEK->value => [
        ExternalUrlType::PUBLICATION_PAGE->value,
        ExternalUrlType::DECISION_PAGE->value,
    ],
    PetitionVariant::BEROEP->value => [
        ExternalUrlType::PUBLICATION_PAGE->value,
        ExternalUrlType::DECISION_PAGE->value,
    ],
    PetitionVariant::BEZWAAR->value => [
        ExternalUrlType::PUBLICATION_PAGE->value,
        ExternalUrlType::DECISION_PAGE->value,
    ],
];
