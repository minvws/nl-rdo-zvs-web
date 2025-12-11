<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Enums\CustomDateLabel;
use App\Enums\TermType;

class PetitionExcelExport
{
    public static function excelExportDataProvider1(): array
    {
        return [
            [
                [
                    'number' => 'number',
                    'name' => 'name',
                    'deadline_at' => '2020-01-01',
                    'date_of_entry' => '2021-01-01',
                    'custom_dates' => [
                        ['date' => '2022-01-01', 'dateLabel' => CustomDateLabel::DATE_RULING->value],
                        ['date' => '2022-01-01', 'dateLabel' => CustomDateLabel::DATE_DECISION_ON_APPEAL->value],
                    ],
                    'total_days_of_suspensions' => 0,
                    'custom_petition_properties' => [
                        'Binnen wettelijke termijn',
                        'Binnen afgesproken termijn',
                        'Verdaging',
                        'Gegrond',
                        'Ongegrond',
                        'Verzoek betrof bij nader inzien burgervraag',
                    ],
                    'terms' => [
                        [
                            'type' => TermType::SECOND->value,
                        ],
                    ],
                    'decisions' => [
                        [
                            'date' => null, // to check if the newest is taken
                        ],
                        [
                            'date' => '2022-01-01', // to check if the newest is taken
                        ],
                        [
                            'date' => '2023-01-01',
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function excelExportDataProvider2(): array
    {
        return [
            [
                [
                    'number' => 'number',
                    'name' => 'name',
                    'deadline_at' => '2023-01-01',
                    'date_of_entry' => '2021-01-01',
                    'custom_dates' => [
                        ['date' => '2022-01-01', 'dateLabel' => CustomDateLabel::DATE_RULING->value],
                        ['date' => '2022-01-01', 'dateLabel' => CustomDateLabel::DATE_DECISION_ON_APPEAL->value],
                    ],
                    'total_days_of_suspensions' => 0,
                    'custom_petition_properties' => [],
                    'terms' => [
                        [
                            'start_date' => '2023-01-01',
                            'end_date' => '2023-01-02',
                            'type' => TermType::THIRD->value,
                        ],
                    ],
                    'decisions' => [
                        [
                            'name' => 'test',
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function excelExportDataProvider3(): array
    {
        return [
            [
                [
                    'number' => 'number',
                    'name' => 'name',
                    'deadline_at' => '2023-01-01',
                    'date_of_entry' => '2021-01-01',
                    'custom_dates' => [],
                    'total_days_of_suspensions' => 0,
                    'custom_petition_properties' => [
                        'In overleg met verzoeker',
                    ],
                ],
            ],
        ];
    }
}
