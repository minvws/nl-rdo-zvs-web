<?php

declare(strict_types=1);

use App\Enums\WordTemplateId;

return [
    'filesystem_disk' => 'word_templates',

    'departments' => [
        'team-c' => [
            WordTemplateId::C01->value => [
                'filename' => 'C-01. Ontvangstbevestiging bezwaar.docx',
            ],
            WordTemplateId::C02->value => [
                'filename' => 'C-02. Ontvangstbevestiging pro forma.docx',
            ],
            WordTemplateId::C03->value => [
                'filename' => 'C-03. Ontvangstbevestiging met opvragen gronden initiatief VWS.docx',
            ],
            WordTemplateId::C04->value => [
                'filename' => 'C-04. Ontvangstbevestiging met termijnoverschrijding.docx',
            ],
            WordTemplateId::C05->value => [
                'filename' => 'C-05. Verdagingsbrief.docx',
            ],
        ],

        'team-wjz-klachten' => [
            WordTemplateId::K01->value => [
                'filename' => 'K-01. Format ovb klacht ingediend per post.docx',
            ],
            WordTemplateId::K02->value => [
                'filename' => 'K-02. Format ovb klacht ingediend via email.docx',
            ],
            WordTemplateId::K03->value => [
                'filename' => 'K-03. Format ovb klacht ingediend via webformulier.docx',
            ],
            WordTemplateId::K04->value => [
                'filename' => 'K-04. Format verdaging klacht.docx',
            ],
        ],

        'wjz-bb' => [
            WordTemplateId::WJZ01->value => [
                'filename' => 'WJZ-01. Ontvangstbevestiging Bezwaar Algemeen.docx',
            ],
            WordTemplateId::WJZ02->value => [
                'filename' => 'WJZ-02. Ontvangstbevestiging Pro Forma.docx',
            ],
            WordTemplateId::WJZ03->value => [
                'filename' => 'WJZ-03. Ontvangsbevestiging Verzuimherstel VWS.docx',
            ],
            WordTemplateId::WJZ04->value => [
                'filename' => 'WJZ-04. Ontvangstbevestiging Termijnoverschrijding.docx',
            ],
            WordTemplateId::WJZ05->value => [
                'filename' => 'WJZ-05. Ontvangstbevestiging opvragen gronden initaitef VWS.docx',
            ],
            WordTemplateId::WJZ06->value => [
                'filename' => 'WJZ-06. Ontvangsbevestiging Plank.docx',
            ],
            WordTemplateId::WJZ07->value => [
                'filename' => 'WJZ-07. Verdagingsbrief.docx',
            ],
            WordTemplateId::WJZ08->value => [
                'filename' => 'WJZ-08. Uitnodiging ambtelijk horen Teams.docx',
            ],
            WordTemplateId::WJZ09->value => [
                'filename' => 'WJZ-09. Uitnodiging ambtelijk horen op kantoor.docx',
            ],
            WordTemplateId::WJZ010->value => [
                'filename' => 'WJZ-010. Uitnodiging tel. ambtelijk horen.docx',
            ],
            WordTemplateId::WJZ011->value => [
                'filename' => 'WJZ-011. Nota verweer beleid.docx',
            ],
            WordTemplateId::WJZ012->value => [
                'filename' => 'WJZ-012. Uitnodiging cie. zitting aan beleid.docx',
            ],
            WordTemplateId::WJZ013->value => [
                'filename' => 'WJZ-013. Uitnodiging cie. zitting aan bezwaarde of advocaat.docx',
            ],
            WordTemplateId::WJZ014->value => [
                'filename' => 'WJZ-014. Uitnodiging cie.zitting belanghebbende.docx',
            ],
            WordTemplateId::WJZ015->value => [
                'filename' => 'WJZ-015. Nota bij advies.docx',
            ],
            WordTemplateId::WJZ016->value => [
                'filename' => 'WJZ-016. Brief aan bezwaar over doorzending reactie bob naar rechtbank.docx',
            ],
        ],
    ],
];
