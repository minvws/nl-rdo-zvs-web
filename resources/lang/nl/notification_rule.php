<?php

declare(strict_types=1);

return [
    'model_singular' => 'Notificatie',
    'model_plural' => 'Notificaties',
    'create' => 'Notificatie aanmaken',
    'edit' => 'Notificatie bewerken',
    'delete' => 'Notificatie verwijderen',
    'no_records' => 'Er zijn nog geen notificaties aangemaakt.',
    'delete_confirmation_text' => 'U gaat nu deze notificatie verwijderen. Er worden daarna geen meldingen meer voor verstuurd. Weet u het zeker?',

    'event_type' => 'Gebeurtenis',
    'event_type_hint' => 'Over welke gebeurtenis gaat de notificatie?',
    'select' => 'Selecteer',
    'days_label' => 'Termijn',
    'days_hint' => 'Hoeveel dagen voor/na moet de notificatie verstuurd worden',
    'days_unit' => 'dagen',
    'timing' => 'Wanneer',
    'timing_hint' => 'Deze notificatie moet voor of na de gebeurtenis verzonden worden',
    'anchor' => 'Actie',
    'anchor_hint' => 'Wat gebeurt er of is er gebeurd met deze gebeurtenis?',
    'active' => 'Actief',
    'event_type_has_no_end' => 'Deze gebeurtenis heeft geen einddatum.',

    'anchors' => [
        'event_date' => 'Vindt plaats',
        'event_end_date' => 'Loopt af',
        'petition_deadline' => 'Beslistermijn eindigt',
    ],
    'timings' => [
        'before' => 'Voorafgaand aan',
        'on' => 'Bij',
        'after' => 'Na',
    ],

    'preview' => 'Voorbeeld',
    'show_preview' => 'Voorbeeld tonen',
    'preview_incomplete' => 'Kies een gebeurtenis, wanneer en een actie om een voorbeeld te tonen.',

    'action' => [
        'event_date' => [
            'before' => 'Vindt plaats over :days',
            'on' => 'Vindt plaats op :date',
            'after' => 'Vond :days geleden plaats',
        ],
        'event_end_date' => [
            'before' => 'Loopt af over :days',
            'on' => 'Loopt af op :date',
            'after' => 'Is :days geleden afgelopen',
        ],
        'petition_deadline' => [
            'before' => 'Beslistermijn eindigt over :days',
            'on' => 'Beslistermijn eindigt op :date',
            'after' => 'Beslistermijn is :days geleden verlopen',
        ],
    ],
    'days' => '{1} :count dag|[0,*] :count dagen',
    'immediately' => 'Direct',
    'date_placeholder' => '[datum]',
    'mail' => [
        'subject' => 'Notificatie voor zaak :number',
        'greeting' => 'Beste behandelaar,',
        'intro' => 'Er is een notificatie aangemaakt op zaak :number.',
        'instruction' => 'Log in op het zaakvolgsysteem en bekijk de notificatie bij de notificatiebel.',
        'regarding' => 'Betreft: :regarding',
        'action' => 'Actie: :action',
        'automatic' => 'Dit is een automatisch gegenereerd bericht. U kunt hier niet direct op reageren.',
        'signature' => 'Het zaakvolgsysteem',
        'number_placeholder' => '[zaaknummer]',
    ],
];
