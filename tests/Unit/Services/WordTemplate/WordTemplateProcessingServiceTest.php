<?php

declare(strict_types=1);

namespace Tests\Unit\Services\WordTemplate;

use App\Config\Config;
use App\Enums\WordTemplateId;
use App\Services\Petition\WordTemplate\WordTemplateProcessingService;
use App\Services\Petition\WordTemplate\WordTemplateProcessorException;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function sprintf;

class WordTemplateProcessingServiceTest extends TestCase
{
    #[Test]
    public function testProcess(): void
    {
        $disk = Config::string('word_templates.filesystem_disk');
        $filename = Config::string(sprintf('word_templates.departments.team-c.%s.filename', WordTemplateId::C01->value));

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
