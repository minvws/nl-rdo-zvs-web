<?php

declare(strict_types=1);

namespace App\Services\Petition\WordTemplate;

use ErrorException;
use PhpOffice\PhpWord\Exception\CopyFileException;
use PhpOffice\PhpWord\Exception\CreateTemporaryFileException;
use PhpOffice\PhpWord\TemplateProcessor;

class WordTemplateProcessor extends TemplateProcessor
{
    /**
     * @throws WordTemplateProcessorException
     */
    public static function create(string $template): TemplateProcessor
    {
        try {
            return new TemplateProcessor($template);
        } catch (CopyFileException | CreateTemporaryFileException | ErrorException) {
            throw new WordTemplateProcessorException();
        }
    }
}
