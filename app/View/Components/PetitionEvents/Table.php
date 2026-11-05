<?php

declare(strict_types=1);

namespace App\View\Components\PetitionEvents;

use App\Config\DepartmentConfigurationService;
use App\Enums\UnspecifiedAdjournmentMode;
use App\Models\Petition;
use App\Services\PetitionEvent\PetitionEventDeletionService;
use App\Services\PetitionEvent\PetitionEventTermService;
use App\ValueObjects\DeletableEvents;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\WizardEventCollection;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/**
 * The events added in the wizard, with the term of each event and whether it can be deleted.
 */
class Table extends Component
{
    private readonly UnspecifiedAdjournmentMode $adjournmentMode;

    private readonly DeletableEvents $deletableEvents;

    public function __construct(
        public readonly WizardEventCollection $events,
        public readonly Petition $petition,
        DepartmentConfigurationService $configurationService,
        private readonly PetitionEventTermService $termService,
        PetitionEventDeletionService $deletionService,
    ) {
        $this->adjournmentMode = $configurationService->unspecifiedAdjournmentMode($this->petition->department);
        $this->deletableEvents = $deletionService->deletableEvents(
            $this->events,
            $this->petition->petitionType->type,
            $this->adjournmentMode,
        );
    }

    public function termText(PetitionEventData $event): string
    {
        return $this->termService->termText($this->events, $event, $this->adjournmentMode);
    }

    public function canDelete(PetitionEventData $event): bool
    {
        return $this->deletableEvents->contains($event);
    }

    public function render(): View
    {
        return view('components.petition-events.table');
    }
}
