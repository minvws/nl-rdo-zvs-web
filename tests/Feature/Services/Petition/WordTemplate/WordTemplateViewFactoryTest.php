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
            'TEST' => [
                $filename1 => ['filename' => $filename1 . '.docx'],
                $filename2 => ['filename' => $filename2 . '.docx'],
            ],
        ];

        ConfigHelper::set('word_templates.departments', $templates);

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
        ConfigHelper::set('word_templates.departments', []);

        /** @var WordTemplateViewFactory $factory */
        $factory = $this->app->get(WordTemplateViewFactory::class);
        $result = $factory->build();

        $this->assertEmpty($result);
    }

    public function testBuildForDepartment(): void
    {
        $filename1 = $this->faker->unique()->word();
        $filename2 = $this->faker->unique()->word();
        $otherFilename = $this->faker->unique()->word();

        ConfigHelper::set('word_templates.departments', [
            'team-a' => [
                $filename1 => ['filename' => $filename1 . '.docx'],
                $filename2 => ['filename' => $filename2 . '.docx'],
            ],
            'team-b' => [
                $otherFilename => ['filename' => $otherFilename . '.docx'],
            ],
        ]);

        /** @var WordTemplateViewFactory $factory */
        $factory = $this->app->get(WordTemplateViewFactory::class);
        $result = $factory->buildForDepartment('team-a');

        $expected = [
            (object) ['filename' => $filename1 . '.docx', 'word_template_id' => $filename1],
            (object) ['filename' => $filename2 . '.docx', 'word_template_id' => $filename2],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testBuildForDepartmentWithUnknownConfigKey(): void
    {
        ConfigHelper::set('word_templates.departments', [
            'team-a' => [
                $this->faker->word() => ['filename' => $this->faker->word()],
            ],
        ]);

        /** @var WordTemplateViewFactory $factory */
        $factory = $this->app->get(WordTemplateViewFactory::class);
        $result = $factory->buildForDepartment('unknown-department');

        $this->assertEmpty($result);
    }
}
