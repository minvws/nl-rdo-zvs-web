<?php

declare(strict_types=1);

namespace App\Services\Petition\WordTemplate;

use function array_merge;
use function array_values;

class WordTemplateViewFactory
{
    /**
     * @param array<string, array<string, array<string, string>>> $departments
     */
    public function __construct(
        private readonly array $departments,
    ) {
    }

    /**
     * @return array<object{filename: string, word_template_id: string}>
     */
    public function build(): array
    {
        return $this->buildFromTemplates(array_merge([], ...array_values($this->departments)));
    }

    /**
     * @return array<object{filename: string, word_template_id: string}>
     */
    public function buildForDepartment(string $configKey): array
    {
        return $this->buildFromTemplates($this->departments[$configKey] ?? []);
    }

    /**
     * @param array<string, array<string, string>> $templates
     *
     * @return array<object{filename: string, word_template_id: string}>
     */
    private function buildFromTemplates(array $templates): array
    {
        $output = [];
        foreach ($templates as $templateId => $template) {
            $output[] = (object) [
                'filename' => $template['filename'],
                'word_template_id' => $templateId,
            ];
        }

        return $output;
    }
}
