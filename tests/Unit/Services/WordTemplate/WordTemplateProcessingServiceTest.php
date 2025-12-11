<?php

declare(strict_types=1);

namespace Tests\Unit\Services\WordTemplate;

use App\Enums\WordTemplateId;
use App\Services\Petition\WordTemplate\WordTemplateProcessingService;
use App\Services\Petition\WordTemplate\WordTemplateProcessorException;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Helpers\ConfigHelper;
use Tests\TestCase;

use function sprintf;

class WordTemplateProcessingServiceTest extends TestCase
{
    #[Test]
    public function testProcess(): void
    {
        $disk = 'word_templates';
        $filename = 'ovb.pro.forma.docx';

        ConfigHelper::set('word_templates.filesystem_disk', $disk);
        ConfigHelper::set(sprintf('word_templates.templates.%s.filename', WordTemplateId::OVB_PRO_FORMA->value), $filename);

        $wordTemplatePath = Storage::disk($disk)->path($filename);

        $wordTemplateProcessingService = new WordTemplateProcessingService();
        $result = $wordTemplateProcessingService->process((object) ['path' => $wordTemplatePath], []);

        $this->assertStringStartsWith('/tmp/', $result);
        $this->assertStringEndsWith('.docx', $result);
    }

    #[Test]
    public function testProcessWhenTemplateNotFound(): void
    {
        $wordTemplateProcessingService = new WordTemplateProcessingService();

        $this->expectException(WordTemplateProcessorException::class);
        $wordTemplateProcessingService->process((object) ['path' => $this->faker->word()], []);
    }
}
