<?php

declare(strict_types=1);

namespace App\Enums;

enum PetitionDeliverableType: string
{
    case REQUEST_FOR_DOCUMENTS = 'request_for_documents';
    case PROCEDURAL_DOCUMENT = 'procedural_document';
}
