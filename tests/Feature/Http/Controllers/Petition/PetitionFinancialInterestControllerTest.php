<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Enums\TimelineType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Support\Number;
use Tests\Feature\FeatureTestCase;

use function __;

class PetitionFinancialInterestControllerTest extends FeatureTestCase
{
    public function testShow(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create(['amount_in_dispute' => 300_000]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_FINANCIAL_INTEREST_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk()
            ->assertViewIs('petition.financial-interest.show')
            ->assertSee(__('petition.financial_interest'))
            ->assertSee(__('petition.amount_in_dispute'))
            ->assertSee(Number::currency(300_000, 'EUR', 'nl', 0));
    }

    public function testShowWithoutValue(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create(['amount_in_dispute' => null]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_FINANCIAL_INTEREST_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk()
            ->assertDontSee(Number::currency(0, 'EUR', 'nl', 0))
            ->assertSee(__('petition.amount_in_dispute'));
    }

    public function testEdit(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_FINANCIAL_INTEREST_EDIT, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk()
            ->assertSee('name="amount_in_dispute"', false);
    }

    public function testEditWithoutWritePermission(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_FINANCIAL_INTEREST_EDIT, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertForbidden();
    }

    public function testUpdateStoresAndClearsTheAmountInDispute(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department);

        $route = [
            'department' => $department,
            'petition' => $petition,
        ];

        $this->postByRoute(RouteName::DEPARTMENTS_PETITIONS_FINANCIAL_INTEREST_UPDATE, $route, ['amount_in_dispute' => '300000'])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, $route);
        $this->assertSame(300_000, $petition->refresh()->amount_in_dispute);
        $this->assertSame(1, $petition->timelineItems()->where('type', TimelineType::FINANCIAL_INTEREST_UPDATED)->count());

        $this->postByRoute(RouteName::DEPARTMENTS_PETITIONS_FINANCIAL_INTEREST_UPDATE, $route, ['amount_in_dispute' => ''])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, $route);
        $this->assertNull($petition->refresh()->amount_in_dispute);
    }

    public function testUpdateRejectsANegativeAmountInDispute(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_FINANCIAL_INTEREST_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], ['amount_in_dispute' => '-5'])
            ->assertSessionHasErrors('amount_in_dispute');
    }

    public function testUpdateWithHtmx(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE, Permission::PETITION_READ)
            ->fullyVerified()
            ->create();
        $this->beUser($authUser, true, $department)
            ->postByRouteAsHtmx(RouteName::DEPARTMENTS_PETITIONS_FINANCIAL_INTEREST_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], ['amount_in_dispute' => '1500'])
            ->assertViewIs('petition.financial-interest.show');

        $this->assertSame(1_500, $petition->refresh()->amount_in_dispute);
    }

    public function testUpdateWithErrorsAndHxTarget(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $hxTarget = $this->faker->slug(1);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_FINANCIAL_INTEREST_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'amount_in_dispute' => '-5',
                'hx-target' => $hxTarget,
            ])
            ->assertSessionHasErrors('amount_in_dispute')
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_FINANCIAL_INTEREST_EDIT, [
                'department' => $department,
                'petition' => $petition,
                'hx-target' => $hxTarget,
            ]);
    }

    public function testUpdateWithoutWritePermission(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_FINANCIAL_INTEREST_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], ['amount_in_dispute' => '100'])
            ->assertForbidden();
    }
}
