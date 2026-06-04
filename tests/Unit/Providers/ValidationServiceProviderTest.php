<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use App\Enums\PetitionEventType;
use App\Services\EventValidatorInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function app;
use function sprintf;

class ValidationServiceProviderTest extends TestCase
{
    #[Test]
    public function testAllConfiguredEventTypesHaveBindings(): void
    {
        foreach (PetitionEventType::cases() as $type) {
            $key = 'validation.rule.' . $type->value;
            $this->assertTrue(app()->has($key), sprintf('Container missing binding for %s', $type->value));

            $validator = $type->rule();
            $this->assertInstanceOf(
                EventValidatorInterface::class,
                $validator,
                sprintf('Rule() did not resolve validator for %s', $type->value),
            );
        }
    }
}
