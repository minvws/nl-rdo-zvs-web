<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\ExternalUrlType;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionExternalUrl;
use App\Models\PetitionType;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Tests\Feature\FeatureTestCase;

use function __;

class PetitionExternalUrlControllerTest extends FeatureTestCase
{
    public function testEditExternalUrls(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser);

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_EXTERNAL_URLS_EDIT, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertStatus(200);
    }

    public function testEditExternalUrlsWithNonExistingPetition(): void
    {
        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_EXTERNAL_URLS_EDIT, [
                'department' => $this->faker()->word(),
                'petition' => $this->faker()->uuid(),
            ])->assertNotFound();
    }

    public function testViewExternalUrls(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->recycle($petitionType)->create();

        Config::set('external_url.' . $petitionType->type->value, [
            ExternalUrlType::PUBLICATION_PAGE->value,
            ExternalUrlType::DECISION_PAGE->value,
        ]);

        PetitionExternalUrl::factory()->create([
            'petition_id' => $petition->id,
            'petition_external_url_type' => ExternalUrlType::PUBLICATION_PAGE,
            'url' => 'https://example.com/publication',
        ]);

        PetitionExternalUrl::factory()->create([
            'petition_id' => $petition->id,
            'petition_external_url_type' => ExternalUrlType::DECISION_PAGE,
            'url' => 'https://example.com/decision',
        ]);

        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_EXTERNAL_URLS_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk()
            ->assertSee(__('petition.external_urls'))
            ->assertSee('https://example.com/publication')
            ->assertSee('https://example.com/decision');
    }

    public function testUpdateExternalUrlsWithHtmx(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->recycle($petitionType)->create();

        Config::set('external_url.' . $petitionType->type->value, [
            ExternalUrlType::PUBLICATION_PAGE->value,
            ExternalUrlType::DECISION_PAGE->value,
        ]);

        $externalUrls = [
            [
                'petition_external_url_type' => ExternalUrlType::PUBLICATION_PAGE->value,
                'url' => 'https://example.com/updated-publication',
            ],
            [
                'petition_external_url_type' => ExternalUrlType::DECISION_PAGE->value,
                'url' => 'https://example.com/updated-decision',
            ],
        ];

        $authUser = User::factory()->withPermissionsAndDepartment(
            $department,
            Permission::PETITION_WRITE,
            Permission::PETITION_READ,
        )->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRouteAsHtmx(
                RouteName::DEPARTMENTS_PETITIONS_EXTERNAL_URLS_UPDATE,
                [
                    'department' => $department,
                    'petition' => $petition,
                ],
                ['external_urls' => $externalUrls],
            )
            ->assertOk();

        $this->assertDatabaseHas('petition_external_urls', [
            'petition_id' => $petition->id,
            'petition_external_url_type' => ExternalUrlType::PUBLICATION_PAGE->value,
            'url' => 'https://example.com/updated-publication',
        ]);

        $this->assertDatabaseHas('petition_external_urls', [
            'petition_id' => $petition->id,
            'petition_external_url_type' => ExternalUrlType::DECISION_PAGE->value,
            'url' => 'https://example.com/updated-decision',
        ]);
    }

    public function testUpdateExternalUrlsHasErrorsWithHtmx(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->recycle($petitionType)->create();

        $externalUrls = [
            [
                'petition_external_url_type' => ExternalUrlType::PUBLICATION_PAGE->value,
                'url' => 'invalid-url',
            ],
        ];

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRouteAsHtmx(
                RouteName::DEPARTMENTS_PETITIONS_EXTERNAL_URLS_UPDATE,
                [
                    'department' => $department,
                    'petition' => $petition->id,
                ],
                [
                    'hx-target' => $this->faker->word,
                    'external_urls' => $externalUrls,
                ],
            )
            ->assertRedirect();

        $this->assertDatabaseMissing('petition_external_urls', [
            'petition_id' => $petition->id,
            'petition_external_url_type' => ExternalUrlType::PUBLICATION_PAGE->value,
            'url' => 'invalid-url',
        ]);
    }

    public function testUpdateExternalUrlsNoHtmx(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->recycle($petitionType)->create();

        Config::set('external_url.' . $petitionType->type->value, [
            ExternalUrlType::PUBLICATION_PAGE->value,
            ExternalUrlType::DECISION_PAGE->value,
        ]);

        $externalUrls = [
            [
                'petition_external_url_type' => ExternalUrlType::PUBLICATION_PAGE->value,
                'url' => 'https://example.com/new-publication',
            ],
            [
                'petition_external_url_type' => ExternalUrlType::DECISION_PAGE->value,
                'url' => 'https://example.com/new-decision',
            ],
        ];

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_EXTERNAL_URLS_UPDATE,
                [
                    'department' => $department,
                    'petition' => $petition,
                ],
                ['external_urls' => $externalUrls],
            )
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ]);

        $this->assertDatabaseHas('petition_external_urls', [
            'petition_id' => $petition->id,
            'petition_external_url_type' => ExternalUrlType::PUBLICATION_PAGE->value,
            'url' => 'https://example.com/new-publication',
        ]);

        $this->assertDatabaseHas('petition_external_urls', [
            'petition_id' => $petition->id,
            'petition_external_url_type' => ExternalUrlType::DECISION_PAGE->value,
            'url' => 'https://example.com/new-decision',
        ]);
    }

    public function testUpdateExternalUrlsWithEmptyUrlsFiltersOutEmptyValues(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->recycle($petitionType)->create();

        Config::set('external_url.' . $petitionType->type->value, [
            ExternalUrlType::PUBLICATION_PAGE->value,
            ExternalUrlType::DECISION_PAGE->value,
        ]);

        PetitionExternalUrl::factory()->create([
            'petition_id' => $petition->id,
            'petition_external_url_type' => ExternalUrlType::PUBLICATION_PAGE,
            'url' => 'https://example.com/original-publication',
        ]);

        PetitionExternalUrl::factory()->create([
            'petition_id' => $petition->id,
            'petition_external_url_type' => ExternalUrlType::DECISION_PAGE,
            'url' => 'https://example.com/original-decision',
        ]);

        $this->assertDatabaseHas('petition_external_urls', [
            'petition_id' => $petition->id,
            'petition_external_url_type' => ExternalUrlType::PUBLICATION_PAGE->value,
            'url' => 'https://example.com/original-publication',
        ]);

        $this->assertDatabaseHas('petition_external_urls', [
            'petition_id' => $petition->id,
            'petition_external_url_type' => ExternalUrlType::DECISION_PAGE->value,
            'url' => 'https://example.com/original-decision',
        ]);

        $externalUrls = [
            [
                'petition_external_url_type' => ExternalUrlType::PUBLICATION_PAGE->value,
                'url' => 'https://example.com/new-publication',
            ],
            [
                'petition_external_url_type' => ExternalUrlType::DECISION_PAGE->value,
                'url' => '',
            ],
        ];

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_EXTERNAL_URLS_UPDATE,
                [
                    'department' => $department,
                    'petition' => $petition,
                ],
                ['external_urls' => $externalUrls],
            )
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ]);

        $this->assertDatabaseHas('petition_external_urls', [
            'petition_id' => $petition->id,
            'petition_external_url_type' => ExternalUrlType::PUBLICATION_PAGE->value,
            'url' => 'https://example.com/new-publication',
        ]);

        $this->assertDatabaseMissing('petition_external_urls', [
            'petition_id' => $petition->id,
            'petition_external_url_type' => ExternalUrlType::DECISION_PAGE->value,
        ]);

        $this->assertDatabaseMissing('petition_external_urls', [
            'petition_id' => $petition->id,
            'petition_external_url_type' => ExternalUrlType::PUBLICATION_PAGE->value,
            'url' => 'https://example.com/original-publication',
        ]);

        $this->assertDatabaseMissing('petition_external_urls', [
            'petition_id' => $petition->id,
            'petition_external_url_type' => ExternalUrlType::DECISION_PAGE->value,
            'url' => 'https://example.com/original-decision',
        ]);

        $this->assertDatabaseCount('petition_external_urls', 1);
    }

    public function testUpdateExternalUrlsWithWhitespaceOnlyUrlsFiltersOutWhitespace(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->recycle($petitionType)->create();

        Config::set('external_url.' . $petitionType->type->value, [
            ExternalUrlType::PUBLICATION_PAGE->value,
            ExternalUrlType::DECISION_PAGE->value,
        ]);

        $externalUrls = [
            [
                'petition_external_url_type' => ExternalUrlType::PUBLICATION_PAGE->value,
                'url' => '   ',
            ],
            [
                'petition_external_url_type' => ExternalUrlType::DECISION_PAGE->value,
                'url' => "\t\n\r",
            ],
        ];

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_EXTERNAL_URLS_UPDATE,
                [
                    'department' => $department,
                    'petition' => $petition,
                ],
                ['external_urls' => $externalUrls],
            )
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ]);

        $this->assertDatabaseMissing('petition_external_urls', [
            'petition_id' => $petition->id,
        ]);

        $this->assertDatabaseCount('petition_external_urls', 0);
    }

    public function testEditShowsAllConfiguredFieldsEvenWhenSomeUrlsAreEmpty(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->recycle($petitionType)->create();

        Config::set('external_url.' . $petitionType->type->value, [
            ExternalUrlType::PUBLICATION_PAGE->value,
            ExternalUrlType::DECISION_PAGE->value,
        ]);

        PetitionExternalUrl::factory()->create([
            'petition_id' => $petition->id,
            'petition_external_url_type' => ExternalUrlType::PUBLICATION_PAGE,
            'url' => 'https://example.com/publication',
        ]);

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $response = $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_EXTERNAL_URLS_EDIT, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk();

        $response->assertSee(__('external_url_type.' . ExternalUrlType::PUBLICATION_PAGE->value));
        $response->assertSee(__('external_url_type.' . ExternalUrlType::DECISION_PAGE->value));

        $response->assertSee('https://example.com/publication');

        $response->assertSee('name="external_urls[0][petition_external_url_type]"', false);
        $response->assertSee('name="external_urls[1][petition_external_url_type]"', false);
        $response->assertSee('name="external_urls[0][url]"', false);
        $response->assertSee('name="external_urls[1][url]"', false);
    }
}
