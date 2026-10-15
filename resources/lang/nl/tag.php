<?php

declare(strict_types=1);

return [
    'model_singular' => 'Tag',
    'model_plural' => 'Tags',

    'create' => 'Tag toevoegen',
    'edit' => 'Tag bewerken',
    'edit_on_petition' => 'Tags van de zaak wijzigen',
    'delete' => 'Tag verwijderen',

    'name' => 'Naam',
    'color' => 'Kleur',
    'description' => 'Toelichting',
    'description_hint' => 'Eén korte zin over wanneer je deze tag gebruikt.',
    'active' => 'Actief',
    'usage_count' => 'Aantal zaken',

    'no_records' => 'Er zijn nog geen tags ingevoerd.',
    'none' => 'Geen tags',
    'more' => '{1} en nog 1 tag: :names|[2,*] en nog :count tags: :names',
    'inactive_hint' => 'Deze tag is niet meer actief. Haal je hem van de zaak, dan kun je hem niet opnieuw toevoegen.',

    'delete_confirmation_text' => '{0} U gaat nu tag ":name" verwijderen, weet u het zeker?'
        . '|{1} U gaat nu tag ":name" verwijderen. De tag staat op 1 zaak en wordt daar weggehaald, ook in de API. Weet u het zeker?'
        . '|[2,*] U gaat nu tag ":name" verwijderen. De tag staat op :count zaken en wordt daar weggehaald, ook in de API. Weet u het zeker?',
];
