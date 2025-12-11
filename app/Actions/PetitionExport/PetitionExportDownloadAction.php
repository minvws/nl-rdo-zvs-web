<?php

declare(strict_types=1);

namespace App\Actions\PetitionExport;

use App\Models\PetitionExport;
use Illuminate\Filesystem\FilesystemManager;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function sprintf;

class PetitionExportDownloadAction
{
    public function __construct(
        private readonly FilesystemManager $filesystem,
    ) {
    }

    public function execute(PetitionExport $petitionExport): StreamedResponse
    {
        $filesystem = $this->filesystem->disk($petitionExport->disk);

        return $filesystem->download(sprintf('%s.xlsx', $petitionExport->id->toString()));
    }
}
