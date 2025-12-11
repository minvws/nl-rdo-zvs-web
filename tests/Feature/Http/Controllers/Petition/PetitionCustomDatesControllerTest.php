<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\CustomDateLabel;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionType;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use Tests\Feature\FeatureTestCase;

use function __;
use function array_column;
use function sprintf;

class PetitionCustomDatesControllerTest extends FeatureTestCase
{
    public function testEditCustomDates(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_DATES_EDIT, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk()
            ->assertViewIs('form');
    }

    public function testEditCustomDatesWithNonExistingPetition(): void
    {
        $nonExistingPetitionId = $this->faker->uuid;
        $department = Department::factory()
            ->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_DATES_EDIT, [
                'department' => $department,
                'petition' => $nonExistingPetitionId,
            ])
            ->assertNotFound();
    }

    public function testViewCustomDates(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $petitionType->customDateLabels()->create([
            'date_label' => CustomDateLabel::DATE_RULING,
        ]);
        $petition = Petition::factory()
            ->recycle($department)
            ->recycle($petitionType)
            ->create();

        // Create custom dates using the new relationship
        $petition->customDates()->create([
            'date_label' => CustomDateLabel::DATE_RULING,
            'date' => CalendarDate::createFromFormat('Y-m-d', '2021-01-01'),
        ]);
        $petition->customDates()->create([
            'date_label' => CustomDateLabel::DATE_WITHDRAWN,
            'date' => CalendarDate::createFromFormat('Y-m-d', '2021-01-02'),
        ]);

        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_DATES_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk()
            ->assertViewIs('petition.custom-dates.show')
            ->assertSee(__('custom_dates.date_ruling'))
            // This petition has a custom date with this label. However, since the current petitionType
            // is not configured to use this label, the stored date should be
            // ignored and therefor not be shown.
            ->assertDontSee(__('custom_dates.date_withdrawn'));
    }

    public function testUpdateCustomDatesWithHtmx(): void
    {
        $cases = CustomDateLabel::cases();
        $customDates = [
            [
                'date' => $this->faker->calendarDate()->format('Y-m-d'),
                'date_label' => $cases[0]->value,
            ],
            [
                'date' => $this->faker->calendarDate()->format('Y-m-d'),
                'date_label' => $cases[1]->value,
            ],
        ];
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissionsAndDepartment(
            $department,
            Permission::PETITION_WRITE,
            Permission::PETITION_READ,
        )->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRouteAsHtmx(
                RouteName::DEPARTMENTS_PETITIONS_CUSTOM_DATES_UPDATE,
                [
                    'department' => $department,
                    'petition' => $petition,
                ],
                ['custom_dates' => $customDates],
            )
            ->assertOk();

        $petition->refresh();
        $this->assertTrue($petition->customDates->isNotEmpty());

        $caseLabels = $petition->customDates->pluck('date_label')->map(fn($label) => $label->value);
        $this->assertCount(2, $caseLabels);
        $this->assertTrue($caseLabels->contains($cases[0]->value));
        $this->assertTrue($caseLabels->contains($cases[1]->value));
    }

    public function testUpdateCustomDatesHasErrorsWithHtmx(): void
    {
        $customDates = [
            [
                'date' => '99999-11-11',
                'date_label' => $this->faker->randomElement(array_column(CustomDateLabel::cases(), 'value')),
            ],
        ];
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRouteAsHtmx(
                RouteName::DEPARTMENTS_PETITIONS_CUSTOM_DATES_UPDATE,
                [
                    'department' => $department,
                    'petition' => $petition,
                ],
                [
                    'hx-target' => $this->faker->word,
                    'custom_dates' => $customDates,
                ],
            )
            ->assertRedirect();
    }

    public function testUpdateCustomDatesWithNullsAreFilteredOut(): void
    {
        $customDates = [
            [
                'date' => null,
                'date_label' => $this->faker->randomElement(array_column(CustomDateLabel::cases(), 'value')),
            ],
        ];
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_CUSTOM_DATES_UPDATE,
                [
                    'department' => $department,
                    'petition' => $petition,
                ],
                ['custom_dates' => $customDates],
            )
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ]);

        $petition->refresh();
        $this->assertTrue($petition->customDates->isEmpty());
    }

    public function testUpdateCustomDatesWithNonArrayFails(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $response = $this->beUser($authUser, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_CUSTOM_DATES_UPDATE,
                [
                    'department' => $department,
                    'petition' => $petition,
                ],
                [
                    'custom_dates' => $this->faker->word,
                ],
            );

        $response->assertInvalid('custom_dates');
    }

    public function testUpdateCustomDatesWithNonExistingPetition(): void
    {
        $customDates = [
            [
                'date' => $this->faker->calendarDate()->format('Y-m-d'),
                'date_label' => $this->faker->randomElement(array_column(CustomDateLabel::cases(), 'value')),
            ],
        ];
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_DATES_UPDATE, [
                'department' => $department,
                'petition' => $this->faker->uuid,
            ], [
                'custom_dates' => $customDates,
            ])
            ->assertNotFound();
    }

    public function testCustomDatesSetsDateOfCloseWhenNull(): void
    {
        $department = Department::factory()->create();
        $interval = $this->faker->numberBetween(1, 30);
        $date = $this->faker->calendarDate();
        $petition = Petition::factory()->recycle($department)->create([
            'date_of_entry' => $date->subDays($interval),
        ]);

        $customDates = [
            [
                'date' => $date->format('Y-m-d'),
                'date_label' => CustomDateLabel::DATE_RULING->value,
            ],
        ];

        $authUser = User::factory()->withPermissionsAndDepartment(
            $department,
            Permission::PETITION_WRITE,
            Permission::PETITION_READ,
        )->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_DATES_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'custom_dates' => $customDates,
            ])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ]);
        $this->getByRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ])
            ->assertSeeHtml('<dt>Dagen in behandeling</dt>')
            ->assertSeeHtml(sprintf('<dd>%s</dd>', $interval));
    }

    public function testCustomDatesSetsDateOfCloseWhenNotNull(): void
    {
        $department = Department::factory()->create();
        $interval = $this->faker->numberBetween(1, 30);
        $date = $this->faker->calendarDate();
        $petition = Petition::factory()->recycle($department)->create([
            'date_of_entry' => $date->subDays($interval),
        ]);

        $customDates = [
            [
                'date' => $date->addDay()->format('Y-m-d'),
                'date_label' => CustomDateLabel::DATE_RULING->value,
            ],
        ];

        $authUser = User::factory()->withPermissionsAndDepartment(
            $department,
            Permission::PETITION_WRITE,
            Permission::PETITION_READ,
        )->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_DATES_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'custom_dates' => $customDates,
            ])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ]);
        $this->getByRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ])
            ->assertSeeHtml('<dt>Dagen in behandeling</dt>')
            ->assertSeeHtml(sprintf('<dd>%s</dd>', $interval + 1));
    }

    public function testCustomDatesSetsDateOfCloseWhenMaxDateIsGreater(): void
    {
        $department = Department::factory()->create();
        $interval = $this->faker->numberBetween(1, 30);
        $date = $this->faker->calendarDate();
        $petition = Petition::factory()->recycle($department)->create([
            'date_of_entry' => $date->subDays($interval),
        ]);

        $customDates = [
            [
                'date' => $date->format('Y-m-d'),
                'date_label' => CustomDateLabel::DATE_RULING->value,
            ],
        ];

        $authUser = User::factory()->withPermissionsAndDepartment(
            $department,
            Permission::PETITION_WRITE,
            Permission::PETITION_READ,
        )->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_DATES_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'custom_dates' => $customDates,
            ])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ]);
        $this->getByRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ])
            ->assertSeeHtml('<dt>Dagen in behandeling</dt>')
            ->assertSeeHtml(sprintf('<dd>%s</dd>', $interval));
    }
}
