<?php

declare(strict_types=1);

use App\Enums\OptionalFormFieldSetting;
use App\Enums\PetitionTypeType;
use App\Enums\TermType;

return [

    PetitionTypeType::BEROEP->value => [
        'optional_form_fields' => [
            'name' => OptionalFormFieldSetting::OPTIONAL,
            'description' => OptionalFormFieldSetting::OPTIONAL,
            'date_appealed_decision' => OptionalFormFieldSetting::EXCLUDED,
            'petition_category_id' => OptionalFormFieldSetting::REQUIRED,
        ],
        'petition_terms_enabled' => false,
        'petition_deliverables_enabled' => true,
    ],
    PetitionTypeType::BEZWAAR->value => [
        'optional_form_fields' => [
            'name' => OptionalFormFieldSetting::OPTIONAL,
            'description' => OptionalFormFieldSetting::OPTIONAL,
            'date_appealed_decision' => OptionalFormFieldSetting::REQUIRED,
            'petition_category_id' => OptionalFormFieldSetting::REQUIRED,
        ],
        'petition_terms_enabled' => true,
        'petition_terms' => [
            TermType::OBJECTION_PERIOD,
            TermType::FIRST,
            TermType::COMMITTEE_HEARING,
            TermType::SECOND,
            TermType::THIRD,
            TermType::SUSPENSION,
            TermType::SPECIFIED_ADJOURNMENT,
            TermType::NOTICE_OF_DEFAULT,
            TermType::APPEAL_NOT_TIMELY,
            TermType::PENALTY,
        ],
        'petition_deliverables_enabled' => false,
    ],
    PetitionTypeType::WOO_VERZOEK->value => [
        'optional_form_fields' => [
            'name' => OptionalFormFieldSetting::REQUIRED,
            'description' => OptionalFormFieldSetting::OPTIONAL,
            'date_appealed_decision' => OptionalFormFieldSetting::EXCLUDED,
            'petition_category_id' => OptionalFormFieldSetting::EXCLUDED,
        ],
        'petition_terms_enabled' => true,
        'petition_terms' => [
            TermType::FIRST,
            TermType::SECOND,
            TermType::THIRD,
            TermType::SUSPENSION,
            TermType::NOTICE_OF_DEFAULT,
            TermType::APPEAL_NOT_TIMELY,
            TermType::PENALTY,
        ],
        'petition_deliverables_enabled' => false,
    ],

];
