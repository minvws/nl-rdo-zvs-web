<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\PublicHoliday;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class PublicHolidayControllerTest extends FeatureTestCase
{
    #[Test]
    public function testIndex(): void
    {
        Department::factory()->create();
        $publicHoliday = PublicHoliday::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::PUBLIC_HOLIDAY_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_PUBLIC_HOLIDAY_INDEX)
            ->assertOk()
            ->assertSee($publicHoliday->name)
            ->assertViewIs('public-holiday.index');
    }

    #[Test]
    public function testCreate(): void
    {
        Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::PUBLIC_HOLIDAY_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_PUBLIC_HOLIDAY_CREATE)
            ->assertOk()
            ->assertViewIs('public-holiday.create');
    }

    #[Test]
    public function testStore(): void
    {
        $name = $this->faker->word();
        $date = $this->faker->calendarDate();

        $authUser = User::factory()->withPermissions(Permission::PUBLIC_HOLIDAY_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->fromRoute(RouteName::ADMIN_PUBLIC_HOLIDAY_CREATE)
            ->postByRoute(RouteName::ADMIN_PUBLIC_HOLIDAY_STORE, [
                'name' => $name,
                'date' => $date->format('Y-m-d'),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(PublicHoliday::class, [
            'name' => $name,
            'date' => $date,
        ]);
    }

    #[Test]
    public function testStoreWithInvalidDateFormat(): void
    {
        $name = $this->faker->word();

        $authUser = User::factory()->withPermissions(Permission::PUBLIC_HOLIDAY_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->fromRoute(RouteName::ADMIN_PUBLIC_HOLIDAY_CREATE)
            ->postByRoute(RouteName::ADMIN_PUBLIC_HOLIDAY_STORE, [
                'name' => $name,
                'date' => $this->faker->uuid()->toString(),
            ])
            ->assertSessionHasErrors('date');
    }

    #[Test]
    public function testStoreWithInvalidDateValue(): void
    {
        $name = $this->faker->word();

        $authUser = User::factory()->withPermissions(Permission::PUBLIC_HOLIDAY_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->fromRoute(RouteName::ADMIN_PUBLIC_HOLIDAY_CREATE)
            ->postByRoute(RouteName::ADMIN_PUBLIC_HOLIDAY_STORE, [
                'name' => $name,
                'date' => [$this->faker->uuid()->toString()],
            ])
            ->assertSessionHasErrors('date');
    }

    #[Test]
    public function testEdit(): void
    {
        Department::factory()->create();
        $publicHoliday = PublicHoliday::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::PUBLIC_HOLIDAY_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_PUBLIC_HOLIDAY_EDIT, ['publicHoliday' => $publicHoliday])
            ->assertOk()
            ->assertViewIs('public-holiday.edit');
    }

    #[Test]
    public function testUpdate(): void
    {
        $publicHoliday = PublicHoliday::factory()->create();
        $name = $this->faker->name();
        $date = $this->faker->calendarDate()->format('Y-m-d');

        $authUser = User::factory()->withPermissions(Permission::PUBLIC_HOLIDAY_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->fromRoute(RouteName::ADMIN_PUBLIC_HOLIDAY_EDIT, ['publicHoliday' => $publicHoliday])
            ->postByRoute(RouteName::ADMIN_PUBLIC_HOLIDAY_UPDATE, ['publicHoliday' => $publicHoliday], data: [
                'name' => $name,
                'date' => $date,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(PublicHoliday::class, [
            'name' => $name,
            'date' => $date,
        ]);
    }

    #[Test]
    public function testEditWithWrongIdThrowsException(): void
    {
        $authUser = User::factory()->withPermissions(Permission::PUBLIC_HOLIDAY_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_PUBLIC_HOLIDAY_EDIT, ['publicHoliday' => $this->faker->uuid])
            ->assertNotFound();
    }

    #[Test]
    public function testDelete(): void
    {
        $publicHoliday = PublicHoliday::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::PUBLIC_HOLIDAY_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->deleteByRoute(RouteName::ADMIN_PUBLIC_HOLIDAY_DELETE, ['publicHoliday' => $publicHoliday])
            ->assertRedirect();
    }
}
