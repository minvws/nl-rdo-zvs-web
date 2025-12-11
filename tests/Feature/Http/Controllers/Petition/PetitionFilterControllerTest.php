<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\PetitionCriteria;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\Feature\FeatureTestCase;

use function route;

class PetitionFilterControllerTest extends FeatureTestCase
{
    public function testFilterRedirectsToIndex(): void
    {
        $department = Department::factory()->create();
        $searchTerm = Str::random(8);

        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX_FILTER, [
                'department' => $department,
            ], [
                'filter' => [
                    PetitionCriteria::SEARCH->value => $searchTerm,
                ],
            ])
            ->assertRedirect(route(RouteName::DEPARTMENTS_PETITIONS_INDEX, [
                'department' => $department,
                'filter' => [
                    PetitionCriteria::SEARCH->value => $searchTerm,
                ],
            ]));
    }
}
