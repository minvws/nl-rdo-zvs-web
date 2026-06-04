<?php

declare(strict_types=1);

namespace App\Actions\Petition\PetitionEventCreation;

use App\Actions\Petition\Contracts\PetitionEventsCreationStrategyInterface;
use App\Enums\PetitionVariant;

readonly class PetitionEventCreationStrategyResolver
{
    public function __construct(
        private BeroepPetitionEventsCreationStrategy $beroepStrategy,
        private ObjectionPetitionEventsCreationStrategy $bezwaarStrategy,
        private WooVerzoekPetitionEventsCreationStrategy $wooVerzoekStrategy,
    ) {
    }

    public function resolve(PetitionVariant $type): PetitionEventsCreationStrategyInterface
    {
        return match ($type) {
            PetitionVariant::BEROEP => $this->beroepStrategy,
            PetitionVariant::BEZWAAR => $this->bezwaarStrategy,
            PetitionVariant::WOO_VERZOEK => $this->wooVerzoekStrategy,
        };
    }
}
