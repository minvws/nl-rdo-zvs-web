<?php

declare(strict_types=1);

namespace Tests\Feature\Decision;

use App\Enums\Authorization\DepartmentRole;
use App\Models\Decision;
use App\Models\Department;
use App\Models\Team;
use Database\Factories\DecisionFactory;
use Database\Factories\TeamFactory;
use Database\Factories\UserFactory;
use Tests\Feature\FeatureTestCase;

class DecisionTeamLinkingTest extends FeatureTestCase
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

    public function testDecisionCanBeCreatedWithTeamId(): void
    {
        $department = $this->department;

        /** @var Team $team */
        $team = TeamFactory::new()
            ->for($department)
            ->create();

        /** @var Decision $decision */
        $decision = DecisionFactory::new()
            ->for($department)
            ->create(['team_id' => $team->id]);

        $this->assertTrue($decision->team_id->equals($team->id));
        $this->assertEquals($team->name, $decision->team->name);
    }

    public function testDecisionTeamCanBeUpdated(): void
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

        /** @var Decision $decision */
        $decision = DecisionFactory::new()
            ->for($department)
            ->create(['team_id' => $team1->id]);

        $decision->update(['team_id' => $team2->id]);

        $this->assertTrue($decision->refresh()->team_id->equals($team2->id));
    }

    public function testDecisionTeamCanBeSetToNull(): void
    {
        $department = $this->department;

        /** @var Team $team */
        $team = TeamFactory::new()
            ->for($department)
            ->create();

        /** @var Decision $decision */
        $decision = DecisionFactory::new()
            ->for($department)
            ->create(['team_id' => $team->id]);

        $decision->update(['team_id' => null]);

        $this->assertNull($decision->refresh()->team_id);
    }
}
