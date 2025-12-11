<?php

declare(strict_types=1);

namespace Tests\Feature\Rules;

use App\Rules\VirusscannerRule;
use App\Services\Virusscanner\VirusscannerInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Mockery\MockInterface;
use Psr\Log\NullLogger;
use Tests\Feature\FeatureTestCase;

use function sprintf;

class VirusscannerRuleTest extends FeatureTestCase
{
    public function testFailWithoutUploadFile(): void
    {
        $validated = true;

        /** @var VirusscannerRule $virusscanner */
        $virusscanner = $this->app->get(VirusscannerRule::class);
        $virusscanner->validate($this->faker->word(), $this->faker->word(), function () use (&$validated): void {
            $validated = false;
        });

        $this->assertFalse($validated);
    }

    public function testValidationWithRealFile(): void
    {
        $validated = true;

        /** @var VirusscannerInterface&MockInterface $virusscannerService */
        $virusscannerService = $this->mock(VirusscannerInterface::class, function (MockInterface $mock): void {
            $mock->expects('isResourceClean')
                ->andReturn(true);
        });
        $virusscanner = new VirusscannerRule(new NullLogger(), $virusscannerService);

        $file = sprintf('/tmp/%s.%s', $this->faker->uuid(), $this->faker->fileExtension());
        File::put($file, $this->faker->word());
        $uploadedFile = new UploadedFile($file, $this->faker->word());

        $virusscanner->validate($this->faker->word(), $uploadedFile, function () use (&$validated): void {
            $validated = false;
        });

        $this->assertTrue($validated);
    }

    public function testFailedValidationOnNonCleanResource(): void
    {
        $validated = true;

        /** @var VirusscannerInterface&MockInterface $virusscannerService */
        $virusscannerService = $this->mock(VirusscannerInterface::class, function (MockInterface $mock): void {
            $mock->expects('isResourceClean')
                ->andReturn(false);
        });
        $virusscanner = new VirusscannerRule(new NullLogger(), $virusscannerService);

        $file = sprintf('/tmp/%s.%s', $this->faker->uuid(), $this->faker->fileExtension());
        File::put($file, $this->faker->word());
        $uploadedFile = new UploadedFile($file, $this->faker->word());

        $virusscanner->validate($this->faker->word(), $uploadedFile, function () use (&$validated): void {
            $validated = false;
        });

        $this->assertFalse($validated);
    }
}
