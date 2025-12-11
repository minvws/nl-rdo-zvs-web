<?php

declare(strict_types=1);

namespace App\Services\Petition\WordTemplate;

class WordTemplateViewFactory
{
    /**
     * @param array<string, array<string>> $templates
     */
    public function __construct(
        private readonly array $templates,
    ) {
    }

    /**
     * @return array<object{filename: string, word_template_id: string}>
     */
    public function build(): array
    {
        $output = [];
        foreach ($this->templates as $templateId => $template) {
            $output[] = (object) [
                'filename' => $template['filename'],
                'word_template_id' => $templateId,
            ];
        }

        return $output;
    }
}
