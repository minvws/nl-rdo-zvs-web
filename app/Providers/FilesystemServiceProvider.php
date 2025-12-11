<?php

declare(strict_types=1);

namespace App\Providers;

use App\Config\Config;
use App\Repositories\WordTemplate\FilesystemWordTemplateRepository;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Override;

class FilesystemServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Date::use(CarbonImmutable::class);
    }

    #[Override]
    public function register(): void
    {
        $this->app->when(FilesystemWordTemplateRepository::class)
            ->needs(Filesystem::class)
            ->give(static function (): Filesystem {
                return Storage::disk(Config::string('word_templates.filesystem_disk'));
            });
    }
}
