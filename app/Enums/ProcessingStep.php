<?php

declare(strict_types=1);

namespace App\Enums;

enum ProcessingStep: string
{
    case SEARCH_QUESTION = 'search-question';
    case STAKEHOLDER = 'stakeholder';
    case INTAKE = 'intake';
    case DOCUMENT_RECEPTION = 'document-reception';
    case DOCUMENT_ASSESSMENT = 'document-assessment';
    case OPINION = 'opinion';
    case DECISION_NOTE = 'decision-note';
    case REVIEW = 'review';
    case DECISION_LINE = 'decision-line';
    case INVENTORY = 'inventory';
    case ASSESSMENT = 'assessment';
    case CHECK = 'check';
    case PUBLISH = 'publish';
}
