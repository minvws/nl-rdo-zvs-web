<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Faker\Generator;
use Illuminate\Database\Eloquent\Factories\Factory as IlluminateFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @property Generator $faker
 *
 * @extends IlluminateFactory<TModel>
 */
abstract class Factory extends IlluminateFactory
{
}
