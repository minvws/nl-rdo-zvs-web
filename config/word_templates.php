<?php

declare(strict_types=1);

use App\Enums\WordTemplateId;

return [
    'filesystem_disk' => 'word_templates',

    'templates' => [
        WordTemplateId::NOTA_VERWEER->value => [
            'filename' => 'Nota.Verweer.VWS.docx',
        ],
        WordTemplateId::OB2_VERZUIMHERSTEL->value => [
            'filename' => 'OB2.verzuimherstel.VWS.docx',
        ],
        WordTemplateId::OB3_TERMIJNOVEERSCHRIJDING->value => [
            'filename' => 'OB3.termijnoverschrijding.docx',
        ],
        WordTemplateId::OB4_VERDAGING->value => [
            'filename' => 'OB4.verdaging.VWS.docx',
        ],
        WordTemplateId::ONTVANGSTBEVESTIGING_BEZWAAR->value => [
            'filename' => 'Ontvangstbevestiging.bezwaar.docx',
        ],
        WordTemplateId::ONTVANGSTBEVESTIGING_PLANK->value => [
            'filename' => 'Ontvangstbevestiging.plank.docx',
        ],
        WordTemplateId::OBV_MET_OPVRAGEN_GRONDEN_INITIATIEF->value => [
            'filename' => 'ovb.met.opvragen.gronden.initiatief.VWS.docx',
        ],
        WordTemplateId::OVB_PRO_FORMA->value => [
            'filename' => 'ovb.pro.forma.docx',
        ],
    ],
];
