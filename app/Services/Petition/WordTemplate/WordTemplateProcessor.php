<?php

declare(strict_types=1);

namespace App\Services\Petition\WordTemplate;

use ErrorException;
use Override;
use PhpOffice\PhpWord\Exception\CopyFileException;
use PhpOffice\PhpWord\Exception\CreateTemporaryFileException;
use PhpOffice\PhpWord\TemplateProcessor;

use function str_replace;

class WordTemplateProcessor extends TemplateProcessor
{
    /**
     * @throws WordTemplateProcessorException
     */
    public static function create(string $template): self
    {
        try {
            return new self($template);
        } catch (CopyFileException | CreateTemporaryFileException | ErrorException) {
            throw new WordTemplateProcessorException();
        }
    }

    /**
     * Ensure all <w:t> tags have xml:space="preserve" to prevent Word from
     * trimming leading/trailing whitespace adjacent to replaced macro values.
     *
     * @see https://github.com/PHPOffice/PHPWord/issues/590
     * @see https://github.com/PHPOffice/PHPWord/issues/637
     * @see http://www.jenitennison.com/2007/07/13/things-that-make-me-scream-xmlspacepreserve-in-wordml.html
     */
    //@codeCoverageIgnoreStart

    #[Override]
    public function save(): string
    {
        $this->tempDocumentMainPart = str_replace('<w:t>', '<w:t xml:space="preserve">', $this->tempDocumentMainPart);

        foreach ($this->tempDocumentHeaders as &$header) {
            $header = str_replace('<w:t>', '<w:t xml:space="preserve">', $header);
        }

        foreach ($this->tempDocumentFooters as &$footer) {
            $footer = str_replace('<w:t>', '<w:t xml:space="preserve">', $footer);
        }

        return parent::save();
    }
    //@codeCoverageIgnoreEnd
}
