<?php

declare(strict_types=1);

namespace Tests;

use App\Faker\Generator;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;

/**
 * @property Generator $faker
 */
abstract class TestCase extends BaseTestCase
{
    use WithFaker;
}
