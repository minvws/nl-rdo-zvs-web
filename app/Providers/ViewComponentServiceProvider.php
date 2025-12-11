<?php

declare(strict_types=1);

namespace App\Providers;

use App\View\Components\Petition\PetitionDeliverables\Table as PetitionDeliverablesTable;
use App\View\Components\Petition\PetitionTerms\CreateButtons as PetitionTermCreateButtons;
use App\View\Components\Petition\PetitionTerms\Table as PetitionTermsTable;
use Illuminate\Support\ServiceProvider;

class ViewComponentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->when([
            PetitionDeliverablesTable::class,
            PetitionTermsTable::class,
            PetitionTermCreateButtons::class,
        ])
            ->needs('$petitionTypeTypeConfig')
            ->giveConfig('petition_type_type');
    }
}
