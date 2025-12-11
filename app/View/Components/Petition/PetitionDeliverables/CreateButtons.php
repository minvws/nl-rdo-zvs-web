<?php

declare(strict_types=1);

namespace App\View\Components\Petition\PetitionDeliverables;

use App\Enums\PetitionDeliverableType;
use App\Models\Petition;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

class CreateButtons extends Component
{
    public function __construct(
        public Petition $petition,
    ) {
    }

    public function render(): View
    {
        return view('petition.petition-deliverable.create-buttons', [
            'petition' => $this->petition,
            'petitionDeliverableTypes' => PetitionDeliverableType::cases(),
        ]);
    }
}
