<?php

declare(strict_types=1);

namespace Tests\Unit\Services\WordTemplate;

use App\Enums\WordTemplateId;
use App\Repositories\WordTemplate\WordTemplateNotFoundException;
use App\Repositories\WordTemplate\WordTemplateRepositoryInterface;
use App\Services\Petition\WordTemplate\WordTemplateException;
use App\Services\Petition\WordTemplate\WordTemplateService;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class WordTemplateServiceTest extends TestCase
{
    #[Test]
    public function testGet(): void
    {
        $wordTemplateId = $this->faker->randomElement(WordTemplateId::cases());

        /** @var WordTemplateRepositoryInterface&MockObject $wordTemplateRepository */
        $wordTemplateRepository = $this->mock(
            WordTemplateRepositoryInterface::class,
            static function (MockInterface $mock) use ($wordTemplateId): void {
                $mock->expects('getById')
                    ->once()
                    ->with($wordTemplateId)
                    ->andReturn((object) []);
            },
        );

        $wordTemplateService = new WordTemplateService($wordTemplateRepository);
        $wordTemplateService->get($wordTemplateId);
    }

    #[Test]
    public function testGetNotFound(): void
    {
        $wordTemplateId = $this->faker->randomElement(WordTemplateId::cases());

        /** @var WordTemplateRepositoryInterface&MockObject $wordTemplateRepository */
        $wordTemplateRepository = $this->mock(
            WordTemplateRepositoryInterface::class,
            static function (MockInterface $mock) use ($wordTemplateId): void {
                $mock->expects('getById')
                    ->once()
                    ->with($wordTemplateId)
                    ->andThrow(new WordTemplateNotFoundException());
            },
        );

        $wordTemplateService = new WordTemplateService($wordTemplateRepository);

        $this->expectException(WordTemplateException::class);
        $wordTemplateService->get($wordTemplateId);
    }
}
