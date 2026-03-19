<?php

declare(strict_types=1);

namespace App\Actions\Petition\PetitionEventCreation;

use App\Actions\Petition\Contracts\PetitionEventsCreationStrategyInterface;
use App\Enums\PetitionTypeType;

readonly class PetitionEventCreationStrategyResolver
{
    public function __construct(
        private BeroepPetitionEventsCreationStrategy $beroepStrategy,
        private ObjectionPetitionEventsCreationStrategy $bezwaarStrategy,
        private WooVerzoekPetitionEventsCreationStrategy $wooVerzoekStrategy,
    ) {
    }

    public function resolve(PetitionTypeType $type): PetitionEventsCreationStrategyInterface
    {
        return match ($type) {
            PetitionTypeType::BEROEP => $this->beroepStrategy,
            PetitionTypeType::BEZWAAR => $this->bezwaarStrategy,
            PetitionTypeType::WOO_VERZOEK => $this->wooVerzoekStrategy,
        };
    }
}
