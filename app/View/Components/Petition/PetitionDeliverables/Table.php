<?php

declare(strict_types=1);

namespace App\View\Components\Petition\PetitionDeliverables;

use App\Models\Petition;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Webmozart\Assert\Assert;

// NOSONAR
class Table extends Component
{
    /**
     * @param array<string, array<string, mixed>> $petitionVariantConfig
     */
    public function __construct(
        private readonly array $petitionVariantConfig,
        public Petition $petition,
    ) {
    }

    public function render(): ?View
    {
        Assert::keyExists($this->petitionVariantConfig, $this->petition->petitionType->type->value);
        Assert::keyExists($this->petitionVariantConfig[$this->petition->petitionType->type->value], 'petition_deliverables_enabled');

        if ($this->petitionVariantConfig[$this->petition->petitionType->type->value]['petition_deliverables_enabled'] !== true) {
            return null;
        }

        $petitionDeliverables = $this->petition->petitionDeliverables
            ->sortBy('deadline_at');

        return $this->view('petition.petition-deliverable.table', [
            'petition' => $this->petition,
            'petitionDeliverables' => $petitionDeliverables,
        ]);
    }
}
