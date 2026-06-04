<?php

declare(strict_types=1);

namespace Tests\Feature\Petition;

use App\Enums\Authorization\DepartmentRole;
use App\Models\Department;
use App\Models\Petition;
use App\Models\Team;
use Database\Factories\PetitionFactory;
use Database\Factories\TeamFactory;
use Database\Factories\UserFactory;
use Tests\Feature\FeatureTestCase;

class PetitionTeamLinkingTest extends FeatureTestCase
{
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create();
        $this->actingAs(
            UserFactory::new()
                ->fullyVerified()
                ->withDepartmentRoles($this->department, DepartmentRole::WRITE)
                ->create(),
        );
    }

    public function testPetitionCanBeCreatedWithTeamId(): void
    {
        $department = $this->department;

        /** @var Team $team */
        $team = TeamFactory::new()
            ->for($department)
            ->create();

        /** @var Petition $petition */
        $petition = PetitionFactory::new()
            ->for($department)
            ->create(['team_id' => $team->id]);

        $this->assertTrue($petition->team_id->equals($team->id));
        $this->assertEquals($team->name, $petition->team->name);
    }

    public function testPetitionTeamCanBeUpdated(): void
    {
        $department = $this->department;

        /** @var Team $team1 */
        $team1 = TeamFactory::new()
            ->for($department)
            ->create();

        /** @var Team $team2 */
        $team2 = TeamFactory::new()
            ->for($department)
            ->create();

        /** @var Petition $petition */
        $petition = PetitionFactory::new()
            ->for($department)
            ->create(['team_id' => $team1->id]);

        $petition->update(['team_id' => $team2->id]);

        $this->assertTrue($petition->refresh()->team_id->equals($team2->id));
    }

    public function testPetitionTeamCanBeSetToNull(): void
    {
        $department = $this->department;

        /** @var Team $team */
        $team = TeamFactory::new()
            ->for($department)
            ->create();

        /** @var Petition $petition */
        $petition = PetitionFactory::new()
            ->for($department)
            ->create(['team_id' => $team->id]);

        $petition->update(['team_id' => null]);

        $this->assertNull($petition->refresh()->team_id);
    }
}
