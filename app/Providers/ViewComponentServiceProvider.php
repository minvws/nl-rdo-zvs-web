<?php

declare(strict_types=1);

namespace App\Providers;

use App\View\Components\Petition\DeadlineOverrideFields as PetitionDeadlineOverrideFields;
use App\View\Components\Petition\Deadlines as PetitionDeadlines;
use App\View\Components\Petition\PetitionDeliverables\Table as PetitionDeliverablesTable;
use App\View\Components\Petition\PetitionTerms\CreateButtons as PetitionTermCreateButtons;
use App\View\Components\Petition\PetitionTerms\Table as PetitionTermsTable;
use Illuminate\Support\ServiceProvider;

class ViewComponentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->when([
            PetitionDeadlines::class,
            PetitionDeadlineOverrideFields::class,
            PetitionDeliverablesTable::class,
            PetitionTermsTable::class,
            PetitionTermCreateButtons::class,
        ])
            ->needs('$petitionVariantConfig')
            ->giveConfig('petition_variant');
    }
}
