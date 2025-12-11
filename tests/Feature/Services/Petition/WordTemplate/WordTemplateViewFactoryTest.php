<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Petition\WordTemplate;

use App\Services\Petition\WordTemplate\WordTemplateViewFactory;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\ConfigHelper;

class WordTemplateViewFactoryTest extends FeatureTestCase
{
    public function testBuild(): void
    {
        $filename1 = $this->faker->unique()->word();
        $filename2 = $this->faker->unique()->word();
        $templates = [
            $filename1 => ['filename' => $filename1 . '.docx'],
            $filename2 => ['filename' => $filename2 . '.docx'],
        ];

        ConfigHelper::set('word_templates.templates', $templates);

        /** @var WordTemplateViewFactory $factory */
        $factory = $this->app->get(WordTemplateViewFactory::class);
        $result = $factory->build();

        $expected = [
            (object) ['filename' => $filename1 . '.docx', 'word_template_id' => $filename1],
            (object) ['filename' => $filename2 . '.docx', 'word_template_id' => $filename2],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testBuildNoConfig(): void
    {
        ConfigHelper::set('word_templates.templates', []);

        /** @var WordTemplateViewFactory $factory */
        $factory = $this->app->get(WordTemplateViewFactory::class);
        $result = $factory->build();

        $this->assertEmpty($result);
    }
}
