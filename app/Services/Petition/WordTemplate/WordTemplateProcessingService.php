<?php

declare(strict_types=1);

namespace App\Services\Petition\WordTemplate;

use PhpOffice\PhpWord\Settings;
use Ramsey\Uuid\Uuid;

use function sprintf;
use function sys_get_temp_dir;

class WordTemplateProcessingService
{
    /**
     * @param object{
     *     path: string,
     * } $wordTemplate
     * @param array<string, string> $replacements
     *
     * @throws WordTemplateProcessorException
     */
    public function process(object $wordTemplate, array $replacements): string
    {
        $path = $this->generateTempPath();

        Settings::setOutputEscapingEnabled(true);

        $processor = WordTemplateProcessor::create($wordTemplate->path);
        $processor->setValues($replacements);
        $processor->saveAs($path);

        return $path;
    }

    private function generateTempPath(): string
    {
        return sprintf('%s/%s.docx', sys_get_temp_dir(), Uuid::uuid7());
    }
}
