<?php

declare(strict_types=1);

namespace App\View\Components\Petition;

use App\Models\Petition;
use App\Services\Petition\PetitionParticularityCollector;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ParticularityLabels extends Component
{
    public function __construct(
        private readonly PetitionParticularityCollector $petitionParticularityCollector,
        public Petition $petition,
    ) {
    }

    public function render(): View
    {
        $particularities = $this->petitionParticularityCollector->collectParticularities($this->petition);

        return $this->view('petition.particularity-labels', [
            'particularities' => $particularities,
        ]);
    }
}
