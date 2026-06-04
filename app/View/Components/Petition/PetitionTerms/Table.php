<?php

declare(strict_types=1);

namespace App\View\Components\Petition\PetitionTerms;

use App\Collections\PetitionTermCollection;
use App\Models\Petition;
use App\Models\PetitionDraftTerm;
use App\Models\PetitionTerm;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Webmozart\Assert\Assert;

use function view;

// NOSONAR
class Table extends Component
{
    /**
     * @param array<string, array<string, mixed>> $petitionVariantConfig
     * @param PetitionTermCollection<int, PetitionTerm> $petitionTerms
     */
    public function __construct(
        private readonly array $petitionVariantConfig,
        public readonly Petition $petition,
        public readonly PetitionTermCollection $petitionTerms,
        public readonly ?PetitionDraftTerm $draftTerm,
        public readonly ?string $departmentSlug = null,
    ) {
    }

    public function render(): ?View
    {
        Assert::keyExists($this->petitionVariantConfig, $this->petition->petitionType->type->value);
        Assert::keyExists($this->petitionVariantConfig[$this->petition->petitionType->type->value], 'petition_terms_enabled');

        if ($this->petitionVariantConfig[$this->petition->petitionType->type->value]['petition_terms_enabled'] !== true) {
            return null;
        }

        return view('petition.petition-terms.table');
    }
}
