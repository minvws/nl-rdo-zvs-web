<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\FormHelper;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class FormHelperTest extends FeatureTestCase
{
    #[Test]
    public function testOldWhenNotSetReturnsNull(): void
    {
        $key = $this->faker->word();

        $this->mock(Request::class, static function (MockInterface $mock) use ($key): void {
            $mock->expects('old')
                ->with($key, null)
                ->andReturn(null);
        });

        $formHelper = $this->getFormHelper();
        $this->assertNull($formHelper->old($key));
    }

    #[Test]
    public function testOldWhenNotSetButWithDefault(): void
    {
        $key = $this->faker->word();
        $default = $this->faker->word();

        $this->mock(Request::class, static function (MockInterface $mock) use ($key, $default): void {
            $mock->expects('old')
                ->with($key, $default)
                ->andReturn($default);
        });

        $formHelper = $this->getFormHelper();
        $this->assertEquals($default, $formHelper->old($key, $default));
    }

    #[Test]
    public function testOldWhenSetReturnsOldValue(): void
    {
        $key = $this->faker->word();
        $oldValue = $this->faker->word();

        $this->mock(Request::class, static function (MockInterface $mock) use ($key, $oldValue): void {
            $mock->expects('old')
                ->with($key, null)
                ->andReturn($oldValue);
        });

        $formHelper = $this->getFormHelper();
        $this->assertEquals($oldValue, $formHelper->old($key));
    }

    #[Test]
    public function testOldWhenSetWithDefaultReturnsOldValue(): void
    {
        $key = $this->faker->word();
        $default = $this->faker->word();
        $oldValue = $this->faker->word();

        $this->mock(Request::class, static function (MockInterface $mock) use ($key, $default, $oldValue): void {
            $mock->expects('old')
                ->with($key, $default)
                ->andReturn($oldValue);
        });

        $formHelper = $this->getFormHelper();
        $this->assertEquals($oldValue, $formHelper->old($key, $default));
    }

    #[Test]
    public function testOldWithInvalidOldValue(): void
    {
        $key = $this->faker->word();

        $this->mock(Request::class, static function (MockInterface $mock) use ($key): void {
            $mock->expects('old')
                ->with($key, null)
                ->andReturn([]);
        });

        $formHelper = $this->getFormHelper();

        $this->expectException(InvalidArgumentException::class);
        $formHelper->old($key);
    }

    #[Test]
    public function testOldArrayWhenNotSetReturnsNull(): void
    {
        $key = $this->faker->word();

        $this->mock(Request::class, static function (MockInterface $mock) use ($key): void {
            $mock->expects('old')
                ->with($key, null)
                ->andReturn(null);
        });

        $formHelper = $this->getFormHelper();
        $this->assertNull($formHelper->oldArray($key));
    }

    #[Test]
    public function testOldArrayWhenNotSetButWithDefault(): void
    {
        $key = $this->faker->word();
        $default = [$this->faker->word()];

        $this->mock(Request::class, static function (MockInterface $mock) use ($key, $default): void {
            $mock->expects('old')
                ->with($key, $default)
                ->andReturn($default);
        });

        $formHelper = $this->getFormHelper();
        $this->assertEquals($default, $formHelper->oldArray($key, $default));
    }

    #[Test]
    public function testOldArrayWhenSetReturnsOldValue(): void
    {
        $key = $this->faker->word();
        $oldValue = [$this->faker->word()];

        $this->mock(Request::class, static function (MockInterface $mock) use ($key, $oldValue): void {
            $mock->expects('old')
                ->with($key, null)
                ->andReturn($oldValue);
        });

        $formHelper = $this->getFormHelper();
        $this->assertEquals($oldValue, $formHelper->oldArray($key));
    }

    #[Test]
    public function testOldArrayWhenSetWithDefaultReturnsOldValue(): void
    {
        $key = $this->faker->word();
        $default = [$this->faker->word()];
        $oldValue = [$this->faker->word()];

        $this->mock(Request::class, static function (MockInterface $mock) use ($key, $default, $oldValue): void {
            $mock->expects('old')
                ->with($key, $default)
                ->andReturn($oldValue);
        });

        $formHelper = $this->getFormHelper();
        $this->assertEquals($oldValue, $formHelper->oldArray($key, $default));
    }

    #[Test]
    public function testOldArrayWithInvalidOldValue(): void
    {
        $key = $this->faker->word();

        $this->mock(Request::class, static function (MockInterface $mock) use ($key): void {
            $mock->expects('old')
                ->with($key, null)
                ->andReturn('string');
        });

        $formHelper = $this->getFormHelper();

        $this->expectException(InvalidArgumentException::class);
        $formHelper->oldArray($key);
    }

    private function getFormHelper(): FormHelper
    {
        return $this->app->get(FormHelper::class);
    }
}
