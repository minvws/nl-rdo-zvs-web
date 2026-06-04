<?php

declare(strict_types=1);

namespace App\Actions\Petition\TermCreation;

use App\Actions\Petition\Contracts\TermCreationStrategyInterface;
use App\Enums\PetitionVariant;

readonly class TermCreationStrategyResolver
{
    public function __construct(
        private BeroepTermCreationStrategy $beroepStrategy,
        private BezwaarTermCreationStrategy $bezwaarStrategy,
        private WooVerzoekTermCreationStrategy $wooVerzoekStrategy,
    ) {
    }

    public function resolve(PetitionVariant $type): TermCreationStrategyInterface
    {
        return match ($type) {
            PetitionVariant::BEROEP => $this->beroepStrategy,
            PetitionVariant::BEZWAAR => $this->bezwaarStrategy,
            PetitionVariant::WOO_VERZOEK => $this->wooVerzoekStrategy,
        };
    }
}
