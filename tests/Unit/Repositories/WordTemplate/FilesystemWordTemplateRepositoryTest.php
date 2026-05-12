<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\WordTemplate;

use App\Enums\WordTemplateId;
use App\Repositories\WordTemplate\FilesystemWordTemplateRepository;
use App\Repositories\WordTemplate\WordTemplateNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class FilesystemWordTemplateRepositoryTest extends TestCase
{
    #[Test]
    public function testGetById(): void
    {
        /** @var WordTemplateId $wordTemplateId */
        $wordTemplateId = $this->faker->randomElement(WordTemplateId::cases());
        $filename = $this->faker->word();
        $path = $this->faker->word();

        /** @var Filesystem&MockObject $filesystem */
        $filesystem = $this->mock(Filesystem::class, static function (MockInterface $mock) use ($filename, $path): void {
            $mock->expects('exists')
                ->once()
                ->with($filename)
                ->andReturn(true);
            $mock->expects('path')
                ->once()
                ->andReturn($path);
        });
        $departments = [
            'TEST' => [
                $wordTemplateId->value => [
                    'filename' => $filename,
                ],
            ],
        ];

        $filesystemWordTemplateRepository = new FilesystemWordTemplateRepository($filesystem, $departments);
        $result = $filesystemWordTemplateRepository->getById($wordTemplateId);

        $this->assertEquals($wordTemplateId, $result->id);
        $this->assertEquals($filename, $result->filename);
        $this->assertEquals($path, $result->path);
    }

    #[Test]
    public function testGetByIdWhenConfigNotFound(): void
    {
        /** @var Filesystem&MockObject $filesystem */
        $filesystem = $this->mock(Filesystem::class);
        $departments = [];

        $filesystemWordTemplateRepository = new FilesystemWordTemplateRepository($filesystem, $departments);

        $this->expectException(WordTemplateNotFoundException::class);
        $filesystemWordTemplateRepository->getById($this->faker->randomElement(WordTemplateId::cases()));
    }

    #[Test]
    public function testGetByIdWhenFileNotFound(): void
    {
        /** @var WordTemplateId $wordTemplateId */
        $wordTemplateId = $this->faker->randomElement(WordTemplateId::cases());

        /** @var Filesystem&MockObject $filesystem */
        $filesystem = $this->mock(Filesystem::class, function (MockInterface $mock): void {
            $mock->expects('exists')
                ->once()
                ->andReturn(false);
        });
        $departments = [
            'TEST' => [
                $wordTemplateId->value => [
                    'filename' => $this->faker->word(),
                ],
            ],
        ];

        $filesystemWordTemplateRepository = new FilesystemWordTemplateRepository($filesystem, $departments);

        $this->expectException(WordTemplateNotFoundException::class);
        $filesystemWordTemplateRepository->getById($wordTemplateId);
    }
}
