<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Petition\WordTemplate\WordTemplateViewFactory;
use Illuminate\Support\ServiceProvider;
use Override;

class ViewFactoryServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->when(WordTemplateViewFactory::class)
            ->needs('$departments')
            ->giveConfig('word_templates.departments');
    }
}
