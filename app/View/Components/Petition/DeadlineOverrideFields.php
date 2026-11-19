<?php

declare(strict_types=1);

namespace App\View\Components\Petition;

use Illuminate\Contracts\View\View;

class DeadlineOverrideFields extends DeadlinesComponent
{
    public function render(): ?View
    {
        if (!$this->deadlinesEnabled()) {
            return null;
        }

        return $this->view('petition.properties.deadline-override-fields');
    }
}
