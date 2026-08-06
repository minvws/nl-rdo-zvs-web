<?php

declare(strict_types=1);

return [
    'global_message' => 'Vul de ontbrekende velden in om je aanpassing op te kunnen slaan.',
    'date_required' => 'Vul een datum in',
    'calendar_date' => 'Vul een datum in',
    'encrypted_string' => 'Ongeldige versleutelde string',
    'unique_custom_petition_property_grouping' => 'Er mogen niet meerdere eigenschappen uit dezelfde groepering geselecteerd worden.',

    'confirmed' => 'De wachtwoordbevestiging is mislukt',
    'max' => [
        'string' => 'Vul niet meer dan :max tekens in.',
    ],
    'min' => [
        'string' => 'Vul op zijn minst :min tekens in.',
    ],
    'password' => [
        'uncompromised' => 'Dit :attribute is voorgekomen in een datalek. Kies een ander :attribute.',
    ],
    'unique' => 'Deze :attribute is al in gebruik',
    'user_permission' => 'Vul een geldige gebruiker in.',

    'string' => 'Vul een tekstuele waarde in',
    'uuid' => 'Vul een geldig uuid in',

    'required' => 'Het veld :attribute is verplicht.',
    'required_without' => 'Het veld :attribute is verplicht wanneer :values niet ingevuld is.',

    'upload_too_big' => 'Het bestand mag maximaal 20Mb groot zijn.',

    'custom' => [
        'user_id' => [
            'required' => 'Kies een behandelaar',
        ],
        'name' => [
            'required' => 'Vul een naam in',
        ],
        'email' => [
            'required' => 'Vul een e-mailadres in',
            'ends_with' => 'Het :attribute moet eindigen met één van de volgende waarden: :values.',
        ],
        'password' => [
            'required' => 'Vul je wachtwoord in',
        ],
        'current_password' => [
            'required' => 'Vul je huidige wachtwoord in',
        ],
        'code' => [
            'required' => 'Vul de 2FA code in',
            'regex' => 'Vul een geldige 2FA code in',
        ],
        'date' => [
            'required' => 'Kies een datum',
        ],
        'deadline_at' => [
            'required' => 'Kies een datum',
        ],
        'date_of_entry' => [
            'required' => 'Kies een datum',
        ],
        'explanation' => [
            'required' => 'Een toelichting is verplicht',
        ],
        'status_label' => [
            'required' => 'Vul een labeltekst in',
        ],
        'petition_types' => [
            'required' => 'Kies een soort zaak',
        ],
        'type' => [
            'required' => 'Kies een fasetype',
        ],
        'period_in_days' => [
            'required' => 'Vul de duur van de periode in dagen in',
            'integer' => 'Vul een getal in',
        ],
        'can_enter_date_manually' => [
            'required' => 'Kies of een handmatige datum is toegestaan',
        ],
        'startDateLabel' => [
            'required' => 'Vul een labeltekst in voor de startdatum',
        ],
        'hasEndDate' => [
            'required' => 'Kies of de fase wel of geen einddatum heeft',
        ],
        'attachments.*' => [
            'max' => 'De grootte van een bestand mag niet groter zijn dan :max. Probeer de bestandsgrootte aan te passen of neem contact met beheer op.',
            'mimes' => 'De extensie is niet toegestaan. Toegestane extensies zijn :values.',
        ],
        'comment' => [
            'required' => 'Een notitie is hier verplicht',
        ],
        'cause' => [
            'required' => 'Kies een reden voor de deadline wijziging',
        ],
        'start_date' => [
            'required' => 'Een datum is verplicht',
            'before_or_equal' =>'Datum moet eerder of gelijk zijn aan 31-12-2999',
        ],
        'start_date_label' => [
            'required' => 'Een label voor de datum is verplicht',
        ],
        'end_date_label' => [
            'required' => 'Een label voor de einddatum is verplicht',
        ],
        'end_date' => [
            'required' => 'Een einddatum is verplicht',
        ],
        'note_required' => 'Een notitie mag niet leeg zijn',
        'petition_type_id' => [
            'required' => 'Kies een Zaaksoort',
        ],
        'date_from' => [
            'required' => 'Kies een startdatum',
        ],
        'date_to' => [
            'required' => 'Kies een einddatum',
        ],
        'reference' => [
            'required' => 'Vul een referentie in',
            'exists' => 'Deze referentie bestaat niet',
        ],
        'number' => [
            'required' => 'Vul een kenmerk van een zaak in',
            'exists' => 'Deze zaak bestaat niet',
            'unique' => 'Dit zaaknummer is al in gebruik',
            'not_regex' => 'Dit nummer format is gereserveerd voor het Zaakvolgsysteem, graag een ander format gebruiken'
        ],
        'message' => [
            'string' => 'Vul een kenmerk in',
            'max:64' => 'Het kenmerk mag maximaal 64 tekens bevatten',
        ],
        'date_of_message' => [
            'required' => 'Vul een datum in',
        ],
        'duration_in_days' => [
            'required' => 'Vul de duur in',
            'min' => 'De duur moet minimaal 1 dag zijn',
            'max' => 'De duur mag maximaal 9999 dagen zijn',
        ],
        'penalty_amount_in_euros' => [
            'min' => 'De dwangsom moet minimaal 1 euro zijn',
            'max' => 'De dwangsom mag maximaal 10.000 euro zijn',
        ],
        'penalty_terms.*.duration_in_days' => [
            'required_with' => 'De duur is verplicht als er een dwangsom ingevuld is',
            'min' => 'De duur moet minimaal 1 dag zijn',
            'max' => 'De duur mag maximaal 9999 dagen zijn',
        ],
        'penalty_terms.*.penalty_amount_in_euros' => [
            'required_with' => 'De dwangsom is verplicht als er een duur ingevuld is',
            'min' => 'De dwangsom moet minimaal 1 euro zijn',
            'max' => 'De dwangsom mag maximaal 10.000 euro zijn',
        ],
        'custom_costs.*.custom_cost_amount_in_euros' => [
            'required' => 'Vul een bedrag in',
            'min' => 'Het bedrag mag niet negatief zijn',
            'max' => 'Het bedrag mag niet meer dan 100 miljoen euro zijn',
        ],
        'status' => [
            'required' => 'Kies een status',
        ],
        'postal_code' => [
            'regex' => 'Vul een geldige postcode in',
        ],
        'visiting_address_postal_code' => [
            'regex' => 'Vul een geldige postcode in',
        ],
        'postal_address_postal_code' => [
            'regex' => 'Vul een geldige postcode in',
        ],
        'petition_status_date' => [
            'before_or_equal' => 'Vul een datum in die vandaag of eerder is',
        ],
    ],
    'secure_file_upload' => [
        'uploaded_file' => 'De :attribute moet een geüpload bestand zijn.',
        'invalid_extension' => 'De :attribute heeft een extensie die niet is toegestaan of niet overeenkomt met het mime-type.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'email' => 'e-mailadres',
        'name' => 'naam',
        'last_name' => 'Achternaam',
        'organisation_name' => 'Organisatie',
        'attachments.0' => 'bijlage',
    ],

];
