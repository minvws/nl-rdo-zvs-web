<?php

declare(strict_types=1);

namespace Tests;

use App\Faker\Generator;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @property Generator $faker
 */
abstract class TestCase extends BaseTestCase
{
    use WithFaker;

    public function createMethodMock(string $originalClassName, string $method, mixed $returnValue): MockObject
    {
        $mock = $this->createMock($originalClassName);
        $mock->expects($this->once())
            ->method($method)
            ->willReturn($returnValue);

        return $mock;
    }
}
