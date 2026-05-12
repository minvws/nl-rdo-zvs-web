<?php

declare(strict_types=1);

namespace App\Factories;

use App\Models\PetitionEvent;
use App\ValueObjects\PenaltyData;
use App\ValueObjects\PetitionEventData;

use function array_map;

final class PetitionEventDataFactory
{
    public static function fromModel(PetitionEvent $event): PetitionEventData
    {
        /** @var array<int, PenaltyData> $penalties */
        $penalties = array_map(
            static function (array $penalty): PenaltyData {
                return new PenaltyData(
                    amount: (int) $penalty['amount'],
                    duration: (int) $penalty['duration'],
                );
            },
            $event->penalties ?? [],
        );

        return new PetitionEventData(
            type: $event->type,
            date: $event->date,
            createdAt: $event->created_at->toImmutable(),
            duration: $event->duration,
            penalties: $penalties,
            suspensionType: $event->suspension_type,
            resultType: $event->result_type,
            hearingForm: $event->hearing_form,
        );
    }
}
