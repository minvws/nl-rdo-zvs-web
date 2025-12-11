<?php

declare(strict_types=1);

namespace App\View\Components\Petition;

use App\Factories\View\Petition\PetitionCustomDatesViewFactory;
use App\Models\Petition;
use App\Services\Petition\PetitionException;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CustomDates extends Component
{
    public function __construct(
        private readonly PetitionCustomDatesViewFactory $petitionCustomDatesViewFactory,
        public Petition $petition,
    ) {
    }

    /**
     * @throws PetitionException
     */
    public function render(): View
    {
        $customDates = $this->petitionCustomDatesViewFactory->build($this->petition);

        return $this->view('petition.custom-dates.list', [
            'custom_dates' => $customDates,
        ]);
    }
}
