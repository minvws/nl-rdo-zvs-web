<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class UserModelTest extends FeatureTestCase
{
    #[Test]
    public function testAssignedPetitionsRelationship(): void
    {
        $user = User::factory()->create();
        $this->assertInstanceOf(HasMany::class, $user->petitionAssignments());
    }

    #[Test]
    public function testProcessingStepAssignmentsRelationship(): void
    {
        $user = User::factory()->create();
        $this->assertInstanceOf(HasMany::class, $user->processingStepAssignments());
    }

    #[Test]
    public function testAssignedProcessingStepsRelationship(): void
    {
        $user = User::factory()->create();
        $this->assertInstanceOf(HasMany::class, $user->assignedProcessingSteps());
    }
}
