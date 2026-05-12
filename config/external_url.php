<?php

declare(strict_types=1);

use App\Enums\ExternalUrlType;
use App\Enums\PetitionTypeType;

return [
    PetitionTypeType::WOO_VERZOEK->value => [
        ExternalUrlType::PUBLICATION_PAGE->value,
        ExternalUrlType::DECISION_PAGE->value,
    ],
    PetitionTypeType::BEROEP->value => [
        ExternalUrlType::PUBLICATION_PAGE->value,
        ExternalUrlType::DECISION_PAGE->value,
    ],
    PetitionTypeType::BEZWAAR->value => [
        ExternalUrlType::PUBLICATION_PAGE->value,
        ExternalUrlType::DECISION_PAGE->value,
    ],
];
