<?php

declare(strict_types=1);

namespace App\Actions\Petition\TermCreation;

use App\Actions\Petition\Contracts\TermCreationStrategyInterface;
use App\Enums\PetitionTypeType;

readonly class TermCreationStrategyResolver
{
    public function __construct(
        private BeroepTermCreationStrategy $beroepStrategy,
        private BezwaarTermCreationStrategy $bezwaarStrategy,
        private WooVerzoekTermCreationStrategy $wooVerzoekStrategy,
    ) {
    }

    public function resolve(PetitionTypeType $type): TermCreationStrategyInterface
    {
        return match ($type) {
            PetitionTypeType::BEROEP => $this->beroepStrategy,
            PetitionTypeType::BEZWAAR => $this->bezwaarStrategy,
            PetitionTypeType::WOO_VERZOEK => $this->wooVerzoekStrategy,
        };
    }
}
