<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Petition;

use App\Actions\Petition\TermCreation\TermCreationStrategyResolver;
use App\Enums\PetitionVariant;
use LogicException;
use Tests\TestCase;

use function app;

class TermCreationStrategyResolverTest extends TestCase
{
    public function testVerzetHasNoTermStrategy(): void
    {
        $this->expectException(LogicException::class);

        app(TermCreationStrategyResolver::class)->resolve(PetitionVariant::VERZET);
    }

    public function testCivieleProcedureHasNoTermStrategy(): void
    {
        $this->expectException(LogicException::class);

        app(TermCreationStrategyResolver::class)->resolve(PetitionVariant::CIVIELE_PROCEDURE);
    }
}
