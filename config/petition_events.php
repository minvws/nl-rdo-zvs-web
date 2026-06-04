<?php

declare(strict_types=1);

use App\Enums\PetitionEventType;
use App\Enums\PetitionVariant;
use App\Enums\ResultType;

return [
    'defaults' => [
        PetitionVariant::BEZWAAR->value => [
            PetitionEventType::PRIMARY_DECISION->value => [
                'duration' => 42,
            ],
            PetitionEventType::RECEIPT_OF_OBJECTION->value => [
                'duration' => 42,
            ],
            PetitionEventType::ADJOURNMENT->value => [
                'duration' => 42,
            ],
            PetitionEventType::MEETING_SCHEDULED->value => [
                'duration' => 42,
            ],
            PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->value => [
                'duration' => 14,
                'penalties' => [
                    ['duration' => 14, 'amount' => 23],
                    ['duration' => 14, 'amount' => 35],
                    ['duration' => 14, 'amount' => 45],
                ],
            ],
            PetitionEventType::APPEAL_DECISION_NOT_TIMELY->value => [
                'duration' => 14,
                'penalties' => [
                    ['duration' => 150, 'amount' => 100],
                ],
            ],
            PetitionEventType::FINAL_RESULT->value => [
                'result_type' => ResultType::FINAL_DECISION,
            ],
        ],
        PetitionVariant::WOO_VERZOEK->value => [
            PetitionEventType::PETITION_RECEIVED->value => [
                'duration' => 28,
            ],
            PetitionEventType::ADJOURNMENT->value => [
                'duration' => 14,
            ],
            PetitionEventType::MEETING_SCHEDULED->value => [],
            PetitionEventType::FINAL_RESULT->value => [
                'result_type' => ResultType::FINAL_DECISION,
            ],
            PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->value => [
                'duration' => 14,
            ],
            PetitionEventType::APPEAL_DECISION_NOT_TIMELY->value => [
                'duration' => 14,
                'penalties' => [
                    ['duration' => 150, 'amount' => 100],
                ],
            ],
        ],
    ],
    'team-c' => [
        PetitionVariant::BEZWAAR->value => [
            PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->value => [
                'duration' => 14,
            ],
        ],
    ],
];
