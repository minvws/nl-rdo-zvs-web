<?php

declare(strict_types=1);

namespace App\Faker;

use Faker\Provider\Uuid as FakerUuid;
use Override;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UuidProvider extends FakerUuid
{
    #[Override]
    public static function uuid(): UuidInterface
    {
        return Uuid::uuid7();
    }
}
