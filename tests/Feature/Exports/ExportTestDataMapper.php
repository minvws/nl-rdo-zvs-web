<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Collections\CustomPetitionPropertyCollection;
use App\Collections\PetitionCustomDateCollection;
use App\Collections\PetitionTermCollection;
use App\Enums\CustomDateLabel;
use App\Enums\TermType;
use App\Models\CustomPetitionProperty;
use App\Models\Decision;
use App\Models\Petition;
use App\Models\PetitionCustomDate as PetitionCustomDateModel;
use App\Models\PetitionTerm;
use App\ValueObjects\CalendarDate;
use Illuminate\Support\Collection;

use function array_map;

trait ExportTestDataMapper
{
    /**
     * @param array<mixed> $data
     */
    protected function mapDataToPetition(array $data): Petition
    {
        $petition = new Petition([
            'number' => $data['number'],
            'name' => $data['name'],
            'deadline_at' => isset($data['deadline_at']) ? CalendarDate::create($data['deadline_at']) : null,
            'date_of_entry' => isset($data['date_of_entry']) ? CalendarDate::create($data['date_of_entry']) : null,
            'total_days_of_suspensions' => $data['total_days_of_suspensions'] ?? 0,
            'customPetitionProperties' => $this->mapCustomProperties($data['custom_petition_properties'] ?? []),
            'petitionTerms' => $this->mapTerms($data['terms'] ?? []),
            'decisions' => $this->mapDecisions($data['decisions'] ?? []),
        ]);

        // Set the custom_dates relation manually using the new approach
        $eloquentCollection = new PetitionCustomDateCollection();

        // Create Eloquent models directly
        foreach ($data['custom_dates'] ?? [] as $customDate) {
            $eloquentCollection->push(new PetitionCustomDateModel([
                'date_label' => CustomDateLabel::from($customDate['dateLabel']),
                'date' => CalendarDate::create($customDate['date']),
            ]));
        }

        $petition->setRelation('customDates', $eloquentCollection);

        return $petition;
    }

    /**
     * @param array<mixed> $terms
     */
    protected function mapTerms(array $terms): PetitionTermCollection
    {
        return new PetitionTermCollection(
            array_map(
                function (array $term) {
                    return new PetitionTerm([
                        'end_date' => isset($term['end_date']) ? CalendarDate::create($term['end_date']) : null,
                        'type' => isset($term['type']) ? TermType::from($term['type']) : null,
                    ]);
                },
                $terms,
            ),
        );
    }

    /**
     * @param array<mixed> $decisions
     */
    protected function mapDecisions(array $decisions): Collection
    {
        return new Collection(
            array_map(
                function (array $decision) {
                    return new Decision([
                        'date' => isset($decision['date']) ? CalendarDate::create($decision['date']) : null,
                    ]);
                },
                $decisions,
            ),
        );
    }

    /**
     * @param array<string> $customProperties
     */
    protected function mapCustomProperties(array $customProperties): CustomPetitionPropertyCollection
    {
        return new CustomPetitionPropertyCollection(
            array_map(
                function (string $property) {
                    return new CustomPetitionProperty([
                        'name' => $property,
                    ]);
                },
                $customProperties,
            ),
        );
    }
}
