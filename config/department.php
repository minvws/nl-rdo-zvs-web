<?php

declare(strict_types=1);

use App\Enums\ProcessingStep;

return [
    'processing-steps' => [
        'team-a' => [
            'regular' => [
                'create_processing_steps_on_decision_creation' => false,
                'options' => [
                    ProcessingStep::INTAKE->value,
                    ProcessingStep::DOCUMENT_RECEPTION->value,
                    ProcessingStep::DOCUMENT_ASSESSMENT->value,
                    ProcessingStep::OPINION->value,
                    ProcessingStep::DECISION_NOTE->value,
                    ProcessingStep::REVIEW->value,
                    ProcessingStep::DECISION_LINE->value,
                ],
            ],
            'chat' => [
                'create_processing_steps_on_decision_creation' => false,
                'options' => [
                    ProcessingStep::SEARCH_QUESTION->value,
                    ProcessingStep::HITS_ASSESSMENT->value,
                    ProcessingStep::ASSESSMENT->value,
                    ProcessingStep::POLICY->value,
                    ProcessingStep::STAKEHOLDER->value,
                    ProcessingStep::DECISION_NOTE->value,
                    ProcessingStep::ACTUAL_DISCLOSURE->value,
                    ProcessingStep::PUBLISH->value,
                ],
            ],
        ],
        'team-b' => [
            'regular' => [
                'create_processing_steps_on_decision_creation' => true,
                'options' => [
                    ProcessingStep::INVENTORY->value,
                    ProcessingStep::ASSESSMENT->value,
                    ProcessingStep::CHECK->value,
                    ProcessingStep::REVIEW->value,
                    ProcessingStep::OPINION->value,
                    ProcessingStep::DECISION_NOTE->value,
                    ProcessingStep::PUBLISH->value,
                ],
            ],
            'chat' => [
                'create_processing_steps_on_decision_creation' => true,
                'options' => [
                    ProcessingStep::SEARCH_QUESTION->value,
                    ProcessingStep::HITS_ASSESSMENT->value,
                    ProcessingStep::ASSESSMENT->value,
                    ProcessingStep::POLICY->value,
                    ProcessingStep::STAKEHOLDER->value,
                    ProcessingStep::DECISION_NOTE->value,
                    ProcessingStep::ACTUAL_DISCLOSURE->value,
                    ProcessingStep::PUBLISH->value,
                ],
            ],
        ],
    ],
];
