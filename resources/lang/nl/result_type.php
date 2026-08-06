<?php

declare(strict_types=1);

return [
    'default' => [
        'final_decision' => 'Finaal besluit',
        'final_decision_55_request' => 'Finaal besluit (5.5-verzoek)',
        'withdrawn' => 'Ingetrokken',
        'forwarded' => 'Doorgezonden (alleen bij volledige doorzending i.c.m. afwijsbesluit)',
        'rejected' => 'Afwijsbesluit (documenten niet aanwezig)',
        'dismissed' => 'Buiten behandeling gesteld (te algemeen, misbruik, herhaalde aanvraag)',
        'reconsidered' => 'Bij nader inzien burgervraag (burgerbrief)',
        'already_public' => 'Verzoek betrof reeds openbare informatie (burgerbrief)',
        'other' => 'Anders',
    ],

    // Per-department label overrides, keyed by department slug. Falls back to the "default" labels above.
    'wjz-bb' => [
        'final_decision' => 'Beslissing op bezwaar',
        'final_decision_55_request' => 'Beslissing op bezwaar (5.5-verzoek)',
    ],
];
