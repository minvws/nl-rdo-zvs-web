<?php

declare(strict_types=1);

namespace App\View\Components\Petition\PetitionTerms;

use App\Enums\PetitionVariant;
use App\Enums\TermType;
use App\Models\Petition;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Webmozart\Assert\Assert;

use function view;

class CreateButtons extends Component
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
        $showDraftTermButton
            = $this->petition->petitionType->type === PetitionVariant::BEZWAAR
            && !$this->petition->draftTerm()->exists()
            && $this->petition->petitionTerms->isNotEmpty();

        Assert::keyExists($this->petitionVariantConfig[$this->petition->petitionType->type->value], 'petition_terms_enabled');
        Assert::boolean($this->petitionVariantConfig[$this->petition->petitionType->type->value]['petition_terms_enabled']);
        if ($this->petitionVariantConfig[$this->petition->petitionType->type->value]['petition_terms_enabled'] === false) {
            return null;
        }

        Assert::keyExists($this->petitionVariantConfig[$this->petition->petitionType->type->value], 'petition_terms');
        Assert::isArray($this->petitionVariantConfig[$this->petition->petitionType->type->value]['petition_terms']);
        Assert::allIsInstanceOf(
            $this->petitionVariantConfig[$this->petition->petitionType->type->value]['petition_terms'],
            TermType::class,
        );

        return view('petition.petition-terms.create-buttons', [
            'petition' => $this->petition,
            'termTypes' => $this->petitionVariantConfig[$this->petition->petitionType->type->value]['petition_terms'],
            'draftTermButton' => $showDraftTermButton,
        ]);
    }
}
