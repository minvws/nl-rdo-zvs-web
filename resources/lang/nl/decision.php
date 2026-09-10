<?php

declare(strict_types=1);

return [
    'model_singular' => 'Besluit',
    'model_plural' => 'Besluiten',

    'all_decisions' => 'Alle besluiten',
    'overview' => 'Besluiten overzicht',
    'no_decisions_found' => 'Er zijn geen besluiten gevonden.',
    'view_decision' => 'Besluit bekijken',

    'created' => 'aangemaakt',
    'finished' => 'afgerond',
    'no_records' => 'Er zijn geen besluiten gevonden.',
    'no_records_linked' => 'Er zijn geen besluiten gekoppeld.',
    'create' => 'Nieuw besluit',
    'edit' => 'Besluit bewerken',
    'link' => 'Bestaand besluit koppelen',
    'name' => 'Naam besluit',
    'reference' => 'Referentienummer',
    'date' => 'Datum besluit',
    'reviewbatch' => 'Reviewbatch',
    'url' => 'URL',
    'detail_page' => 'Overzicht van',
    'edit_properties' => 'Besluitdetails aanpassen',
    'attach' => 'Besluit koppelen',
    'processing_steps' => 'Processtappen',
    'deadline' => 'Deadline',
    'progress' => 'Voortgang',
    'processing_steps_completed' => 'Processtappen afgerond',
    'processing_steps_in_progress' => 'Processtappen in behandeling',

    'attach_petition' => 'Zaak koppelen',
    'attached_petitions' => 'Gekoppelde zaken',

    'archive' => 'Naar prullenbak verplaatsen',
    'archive_confirmation' => 'Besluit naar prullenbak',
    'archive_confirmation_text' => 'U gaat nu Besluit :name naar de prullenbak verplaatsen, weet u het zeker?',
    'unarchive' => 'Uit prullenbak halen',
    'unarchive_confirmation' => 'Besluit uit prullenbak halen',
    'unarchive_confirmation_text' => 'U gaat nu Besluit :name uit de prullenbak halen, weet u het zeker?',
    'detach_confirmation_text' => 'Weet u zeker dat u besluit :decision wilt loskoppelen van zaak :zaak?',
    'archive_button' => ':name naar prullenbak',
    'archived' => 'In prullenbak',
    'unarchived' => 'Uit prullenbak gehaald',

    'filter' => [
        'archive' => [
            'label' => 'Prullenbak',
            'hide_archived' => 'Verberg prullenbak',
            'show_archived' => 'Toon prullenbak',
            'show_all' => 'Toon alles',
        ],
        'type' => [
            'label' => 'Type',
            'all' => 'Alle types',
        ],
        'processing_steps_in_progress' => [
            'label' => 'Processtap in behandeling',
            'all' => 'Alle processtappen',
        ],
    ],

    'type' => [
        'label' => 'Type besluit',
        'chat' => 'Chat besluit',
        'regular' => 'Regulier besluit',
    ],

    'final' => 'Finaal',
    'set_final_decision' => 'Finaal besluit toepassen',
    'no_final_decision' => 'Geen van deze besluiten is finaal',
];
