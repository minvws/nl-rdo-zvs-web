<?php

declare(strict_types=1);

use App\Enums\PetitionTypeType;

return [
    PetitionTypeType::BEROEP->value => [
        'decision_options' => [
            'Gegrond' => 'Deels gegrond',
            'Ongegrond' => 'Niet gegrond',
            'Niet-ontvankelijk' => 'Niet ontvankelijk',
            'Kennelijk niet-ontvankelijk' => 'Niet ontvankelijk',
        ],
    ],
    PetitionTypeType::BEZWAAR->value => [
        'term_options' => [
            'Binnen wettelijke termijn' => 'Binnen wettelijke termijn',
            'Binnen afgesproken termijn' => 'Binnen afgesproken termijn',
            'Buiten wettelijke/afgesproken termijn' => 'Buiten wettelijke/afgesproken termijn',
        ],
        'decision_options' => [
            'Gegrond' => 'Deels gegrond',
            'Kennelijk gegrond' => 'Deels gegrond',
            'Ongegrond' => 'Niet gegrond',
            'Kennelijk ongegrond' => 'Niet gegrond',
            'Niet-ontvankelijk' => 'Niet ontvankelijk',
            'Kennelijk niet-ontvankelijk' => 'Niet ontvankelijk',
        ],
    ],
    PetitionTypeType::WOO_VERZOEK->value => [
        'reason_options' => [
            'Verzoek ingetrokken' => 'Verzoek ingetrokken',
            'Verzoek doorverwezen' => 'Verzoek doorverwezen',
            'Verzoek betrof bij nader inzien burgervraag' => 'Verzoek betrof bij nader inzien burgervraag',
            'Verzoek betrof reeds openbare informatie' => 'Verzoek betrof reeds openbare informatie',
            'Anders' => 'Anders',
        ],
    ],
];
