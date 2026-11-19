<?php

declare(strict_types=1);

namespace App\View\Components\Petition;

use App\Models\Petition;
use App\ValueObjects\CalendarDate;
use Illuminate\Contracts\View\View;

class Deadlines extends DeadlinesComponent
{
    /**
     * @param array<string, array<string, mixed>> $petitionVariantConfig
     */
    public function __construct(
        array $petitionVariantConfig,
        Petition $petition,
        public ?CalendarDate $deadlineNoticeOfDefaultPenaltyPeriodEnd = null,
        public ?CalendarDate $deadlineAppealNotTimelyPenaltyPeriodEnd = null,
    ) {
        parent::__construct($petitionVariantConfig, $petition);
    }

    public function render(): ?View
    {
        if (!$this->deadlinesEnabled()) {
            return null;
        }

        return $this->view('petition.properties.deadlines');
    }
}
