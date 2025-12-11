<?php

declare(strict_types=1);

namespace App\Repositories\WordTemplate;

use App\Enums\WordTemplateId;
use Illuminate\Contracts\Filesystem\Filesystem;
use Webmozart\Assert\Assert;

use function array_key_exists;
use function sprintf;

readonly class FilesystemWordTemplateRepository implements WordTemplateRepositoryInterface
{
    /**
     * @param array<string, array<string, string>> $templates
     */
    public function __construct(
        private Filesystem $filesystem,
        private array $templates,
    ) {
    }

    /**
     * @throws WordTemplateNotFoundException
     */
    public function getById(WordTemplateId $id): object
    {
        if (!array_key_exists($id->value, $this->templates)) {
            throw new WordTemplateNotFoundException(sprintf('id "%s" not found', $id->value));
        }

        $wordTemplateConfig = $this->templates[$id->value];

        Assert::keyExists($wordTemplateConfig, 'filename');
        $filename = $wordTemplateConfig['filename'];

        if (!$this->filesystem->exists($filename)) {
            throw new WordTemplateNotFoundException(sprintf('file "%s" not found', $filename));
        }

        return (object) [
            'id' => $id,
            'filename' => $filename,
            'path' => $this->filesystem->path($filename),
        ];
    }
}
