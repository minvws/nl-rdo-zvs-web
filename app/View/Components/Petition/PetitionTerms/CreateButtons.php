<?php

declare(strict_types=1);

namespace App\View\Components\Petition\PetitionTerms;

use App\Enums\PetitionTypeType;
use App\Enums\TermType;
use App\Models\Petition;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Webmozart\Assert\Assert;

use function view;

class CreateButtons extends Component
{
    /**
     * @param array<string, array<string, mixed>> $petitionTypeTypeConfig
     */
    public function __construct(
        private readonly array $petitionTypeTypeConfig,
        public Petition $petition,
    ) {
    }

    public function render(): ?View
    {
        $showDraftTermButton
            = $this->petition->petitionType->type === PetitionTypeType::BEZWAAR
            && !$this->petition->draftTerm()->exists()
            && $this->petition->petitionTerms->isNotEmpty();

        Assert::keyExists($this->petitionTypeTypeConfig[$this->petition->petitionType->type->value], 'petition_terms_enabled');
        Assert::boolean($this->petitionTypeTypeConfig[$this->petition->petitionType->type->value]['petition_terms_enabled']);
        if ($this->petitionTypeTypeConfig[$this->petition->petitionType->type->value]['petition_terms_enabled'] === false) {
            return null;
        }

        Assert::keyExists($this->petitionTypeTypeConfig[$this->petition->petitionType->type->value], 'petition_terms');
        Assert::isArray($this->petitionTypeTypeConfig[$this->petition->petitionType->type->value]['petition_terms']);
        Assert::allIsInstanceOf(
            $this->petitionTypeTypeConfig[$this->petition->petitionType->type->value]['petition_terms'],
            TermType::class,
        );

        return view('petition.petition-terms.create-buttons', [
            'petition' => $this->petition,
            'termTypes' => $this->petitionTypeTypeConfig[$this->petition->petitionType->type->value]['petition_terms'],
            'draftTermButton' => $showDraftTermButton,
        ]);
    }
}
