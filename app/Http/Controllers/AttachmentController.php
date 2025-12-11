<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Authorization\Permission;
use App\Models\Attachment;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class AttachmentController
{
    public function __construct(
        private FilesystemManager $filesystem,
        private Gate $gate,
    ) {
    }

    public function download(Attachment $attachment): StreamedResponse
    {
        $this->gate->authorize(Permission::PETITION_READ);

        /** @var FilesystemAdapter $filesystem */
        $filesystem = $this->filesystem->disk($attachment->disk);

        return $filesystem->download($attachment->path, $attachment->name);
    }
}
