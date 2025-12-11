<?php

declare(strict_types=1);

namespace App\Enums;

enum WordTemplateId: string
{
    case NOTA_VERWEER = 'nota_verweer';
    case OB2_VERZUIMHERSTEL = 'ob2_verzuimherstel';
    case OB3_TERMIJNOVEERSCHRIJDING = 'ob3_termijnoverschrijding';
    case OB4_VERDAGING = 'ob4_verdaging';
    case ONTVANGSTBEVESTIGING_BEZWAAR = 'ontvangstbevestiging_bezwaar';
    case ONTVANGSTBEVESTIGING_PLANK = 'ontvangstbevestiging_plank';
    case OBV_MET_OPVRAGEN_GRONDEN_INITIATIEF = 'ovb_met_opvragen_gronden_initiatief';
    case OVB_PRO_FORMA = 'ovb_pro_forma';
}
