<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Actions\Petition\QuerysnapshotUpdateAction;
use App\Enums\Authorization\Permission;
use App\Enums\QuerysnapshotType;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionQuerysnapshot;
use App\Models\PetitionType;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\ConfigHelper;

use function __;

class PetitionQuerysnapshotsControllerTest extends FeatureTestCase
{
    public function testEditQuerysnapshots(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser);

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_QUERYSNAPSHOTS_EDIT, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertStatus(200);
    }

    public function testEditQuerysnapshotsWithNonExistingPetition(): void
    {
        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_QUERYSNAPSHOTS_EDIT, [
                'department' => $this->faker()->word(),
                'petition' => $this->faker()->uuid(),
            ])->assertNotFound();
    }

    public function testViewQuerysnapshots(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->recycle($petitionType)->create();

        $this->setQuerysnapshotConfigByPetitionType($petitionType);

        $petitionQuerysnapshot1 = PetitionQuerysnapshot::factory()
            ->for($petition)
            ->create([
                'querysnapshot_type' => QuerysnapshotType::DOCUMENT,
            ]);
        $petitionQuerysnapshot2 = PetitionQuerysnapshot::factory()
            ->for($petition)
            ->create([
                'querysnapshot_type' => QuerysnapshotType::CHAT,
            ]);

        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_QUERYSNAPSHOTS_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk()
            ->assertSee(__('petition.querysnapshots'))
            ->assertSee($petitionQuerysnapshot1->querysnapshot_id)
            ->assertSee($petitionQuerysnapshot2->querysnapshot_id);
    }

    public function testUpdateQuerysnapshotsWithHtmx(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->recycle($petitionType)->create();

        $querysnapshotDocumentId = $this->faker->word();
        $querysnapshotChatId = $this->faker->word();

        $postData = [
            [
                'querysnapshot_type' => QuerysnapshotType::DOCUMENT->value,
                'querysnapshot_id' => $querysnapshotDocumentId,
            ],
            [
                'querysnapshot_type' => QuerysnapshotType::CHAT->value,
                'querysnapshot_id' => $querysnapshotChatId,
            ],
        ];

        $authUser = User::factory()->withPermissionsAndDepartment(
            $department,
            Permission::PETITION_WRITE,
            Permission::PETITION_READ,
        )->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRouteAsHtmx(
                RouteName::DEPARTMENTS_PETITIONS_QUERYSNAPSHOTS_UPDATE,
                [
                    'department' => $department,
                    'petition' => $petition,
                ],
                ['querysnapshots' => $postData],
            )
            ->assertOk();

        $this->assertDatabaseHas(PetitionQuerysnapshot::class, [
            'petition_id' => $petition->id,
            'querysnapshot_type' => QuerysnapshotType::DOCUMENT->value,
            'querysnapshot_id' => $querysnapshotDocumentId,
        ]);
        $this->assertDatabaseHas(PetitionQuerysnapshot::class, [
            'petition_id' => $petition->id,
            'querysnapshot_type' => QuerysnapshotType::CHAT->value,
            'querysnapshot_id' => $querysnapshotChatId,
        ]);
    }

    public function testUpdateQuerysnapshotHasErrorsWithHtmx(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->recycle($petitionType)->create();

        $postData = [
            [
                'querysnapshot_type' => 'invalid',
                'querysnapshot_id' => $this->faker->word(),
            ],
        ];

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRouteAsHtmx(
                RouteName::DEPARTMENTS_PETITIONS_QUERYSNAPSHOTS_UPDATE,
                [
                    'department' => $department,
                    'petition' => $petition->id,
                ],
                [
                    'hx-target' => $this->faker->word(),
                    'querysnapshots' => $postData,
                ],
            )
            ->assertRedirect();

        $this->assertDatabaseMissing(PetitionQuerysnapshot::class, [
            'petition_id' => $petition->id,
        ]);
    }

    public function testUpdateQuerysnapshotsNoHtmx(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->recycle($petitionType)->create();

        $querysnapshotDocumentId = $this->faker->word();
        $querysnapshotChatId = $this->faker->word();

        $postData = [
            [
                'querysnapshot_type' => QuerysnapshotType::DOCUMENT->value,
                'querysnapshot_id' => $querysnapshotDocumentId,
            ],
            [
                'querysnapshot_type' => QuerysnapshotType::CHAT->value,
                'querysnapshot_id' => $querysnapshotChatId,
            ],
        ];

        $authUser = User::factory()->withPermissionsAndDepartment(
            $department,
            Permission::PETITION_WRITE,
            Permission::PETITION_READ,
        )->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_QUERYSNAPSHOTS_UPDATE,
                [
                    'department' => $department,
                    'petition' => $petition,
                ],
                ['querysnapshots' => $postData],
            )
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ]);

        $this->assertDatabaseHas(PetitionQuerysnapshot::class, [
            'petition_id' => $petition->id,
            'querysnapshot_type' => QuerysnapshotType::DOCUMENT->value,
            'querysnapshot_id' => $querysnapshotDocumentId,
        ]);
        $this->assertDatabaseHas(PetitionQuerysnapshot::class, [
            'petition_id' => $petition->id,
            'querysnapshot_type' => QuerysnapshotType::CHAT->value,
            'querysnapshot_id' => $querysnapshotChatId,
        ]);
    }

    public function testUpdateQuerysnapshotsWithEmptyDataFiltersEmptyValues(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->recycle($petitionType)->create();

        PetitionQuerysnapshot::factory()
            ->for($petition)
            ->create([
                'querysnapshot_type' => QuerysnapshotType::DOCUMENT,
            ]);
        PetitionQuerysnapshot::factory()
            ->for($petition)
            ->create([
                'querysnapshot_type' => QuerysnapshotType::CHAT,
            ]);

        $petitionQuerysnapshot1NewId = $this->faker->word();
        $postData = [
            [
                'querysnapshot_type' => QuerysnapshotType::DOCUMENT->value,
                'querysnapshot_id' => $petitionQuerysnapshot1NewId,
            ],
            [
                'querysnapshot_type' => QuerysnapshotType::CHAT->value,
                'querysnapshot_id' => '',
            ],
        ];

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_QUERYSNAPSHOTS_UPDATE,
                [
                    'department' => $department,
                    'petition' => $petition,
                ],
                ['querysnapshots' => $postData],
            );

        $this->assertDatabaseHas(PetitionQuerysnapshot::class, [
            'querysnapshot_id' => $petitionQuerysnapshot1NewId,
        ]);
        $this->assertDatabaseCount(PetitionQuerysnapshot::class, 1);
    }

    public function testUpdateQuerysnapshotUpdatesExistingInsteadOfCreatingDuplicates(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->recycle($petitionType)->create();

        $originalQuerysnapshotDocumentId = 'original-doc-id';
        $originalQuerysnapshotChatId = 'original-chat-id';

        $existingDocument = PetitionQuerysnapshot::factory()
            ->for($petition)
            ->create([
                'querysnapshot_type' => QuerysnapshotType::DOCUMENT,
                'querysnapshot_id' => $originalQuerysnapshotDocumentId,
            ]);
        PetitionQuerysnapshot::factory()
            ->for($petition)
            ->create([
                'querysnapshot_type' => QuerysnapshotType::CHAT,
                'querysnapshot_id' => $originalQuerysnapshotChatId,
            ]);

        $existingDocumentId = $existingDocument->id;

        // Call action directly instead of through controller
        $action = new QuerysnapshotUpdateAction($this->app->make(DatabaseManager::class));
        $user = User::factory()->create();

        $action->execute($petition, $user, [
            'querysnapshots' => [
                ['querysnapshot_type' => QuerysnapshotType::DOCUMENT->value, 'querysnapshot_id' => 'new-doc-id'],
            ],
        ]);

        $petition->refresh();

        $querysnapshots = $petition->querysnapshots()->get();

        $this->assertSame(1, $querysnapshots->count(), 'Expected exactly 1 querysnapshot record. Found: ' . $querysnapshots->count());

        $updatedDocument = $querysnapshots->first();

        $this->assertNotNull($updatedDocument, 'Expected a querysnapshot record to exist');
        $this->assertEquals(
            $existingDocumentId,
            $updatedDocument->id,
            'The existing record should be updated, not replaced with a new one',
        );
        $this->assertSame('new-doc-id', $updatedDocument->querysnapshot_id, 'The querysnapshot_id should be updated');
    }

    public function testEditShowsAllConfiguredFieldsEvenWhenSomeValuesAreEmpty(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->recycle($petitionType)->create();

        $this->setQuerysnapshotConfigByPetitionType($petitionType);

        $petitionQuerysnapshot = PetitionQuerysnapshot::factory()
            ->for($petition)
            ->create([
                'querysnapshot_type' => QuerysnapshotType::DOCUMENT,
            ]);

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $response = $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_QUERYSNAPSHOTS_EDIT, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk();

        $response->assertSee(__('querysnapshot_type.' . QuerysnapshotType::DOCUMENT->value));
        $response->assertSee(__('querysnapshot_type.' . QuerysnapshotType::CHAT->value));

        $response->assertSee($petitionQuerysnapshot->querysnapshot_id);

        $response->assertSee('name="querysnapshots[0][querysnapshot_type]"', false);
        $response->assertSee('name="querysnapshots[1][querysnapshot_type]"', false);
        $response->assertSee('name="querysnapshots[0][querysnapshot_id]"', false);
        $response->assertSee('name="querysnapshots[1][querysnapshot_id]"', false);
    }

    private function setQuerysnapshotConfigByPetitionType(PetitionType $petitionType): void
    {
        ConfigHelper::set('querysnapshot.' . $petitionType->type->value, [
            QuerysnapshotType::DOCUMENT->value,
            QuerysnapshotType::CHAT->value,
        ]);
    }
}
