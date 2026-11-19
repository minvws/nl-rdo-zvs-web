<?php

declare(strict_types=1);

namespace App\View\Components\Petition;

use App\Models\Petition;
use Illuminate\View\Component;
use Webmozart\Assert\Assert;

abstract class DeadlinesComponent extends Component
{
    /**
     * @param array<string, array<string, mixed>> $petitionVariantConfig
     */
    public function __construct(
        private readonly array $petitionVariantConfig,
        public Petition $petition,
    ) {
    }

    protected function deadlinesEnabled(): bool
    {
        Assert::keyExists($this->petitionVariantConfig, $this->petition->petitionType->type->value);
        Assert::keyExists($this->petitionVariantConfig[$this->petition->petitionType->type->value], 'petition_deadlines_enabled');

        return $this->petitionVariantConfig[$this->petition->petitionType->type->value]['petition_deadlines_enabled'] === true;
    }
}
