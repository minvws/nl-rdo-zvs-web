<?php

declare(strict_types=1);

namespace Tests\Smoke\Decision;

use App\Enums\Authorization\DepartmentRole;
use App\Enums\DecisionType;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\Petition;
use App\Models\User;
use Tests\Smoke\SmokeTestCase;

use function __;
use function sprintf;

class DecisionCreateTest extends SmokeTestCase
{
    public function testCreateDecisionActingAs(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $name = $this->faker->sentence();

        DepartmentUser::factory()->create([
            'department_id' => $department->id,
            'user_id' => $user->id,
            'role' => DepartmentRole::WRITE,
        ]);

        $this->beUser($user)
            ->visitRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, ['department' => $department, 'petition' => $petition->id])
            ->see(sprintf('<span class="petition-details__number">%s</span>', $petition->number))
            ->click(__('decision.create'))
            ->see(sprintf('<h1>%s</h1>', __('decision.create')))
            ->type($name, 'name')
            ->type('reference', 'reference')
            ->type('2000-01-01', 'date')
            ->select(DecisionType::REGULAR->value, 'type')
            ->press(__('general.create'))
            ->assertResponseStatus(200)
            ->seeInElement('h1', $name);
    }
}
