<?php

declare(strict_types=1);

namespace App\Actions\PetitionExport;

use App\Models\PetitionExport;
use Illuminate\Database\DatabaseManager;
use Illuminate\Filesystem\FilesystemManager;
use Throwable;

use function sprintf;

class PetitionExportDeleteAction
{
    public function __construct(
        private readonly FilesystemManager $filesystem,
        private readonly DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function execute(PetitionExport $export): void
    {
        $this->databaseManager->transaction(function () use ($export): void {
            $export->delete();
            $filesystem = $this->filesystem->disk($export->disk);
            $filesystem->delete(sprintf('%s.xlsx', $export->id->toString()));
        });
    }
}
