<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Enums\WordTemplateId;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use App\Services\Petition\WordTemplate\WordTemplateException;
use App\Services\Petition\WordTemplate\WordTemplateProcessingService;
use App\Services\Petition\WordTemplate\WordTemplateProcessorException;
use App\Services\Petition\WordTemplate\WordTemplateService;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\ConfigHelper;

use function __;
use function str_repeat;

class PetitionCorrespondenceControllerTest extends FeatureTestCase
{
    public function testList(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();
        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_INDEX, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertStatus(200);
    }

    public function testListWithoutWordConfig(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        ConfigHelper::set('word_templates.templates', []);

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_INDEX, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertStatus(200);
    }

    public function testListNotAuthorized(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();
        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_INDEX, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertStatus(403);
    }

    public function testShow(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();
        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertStatus(200);
    }

    public function testShowRequiresId(): void
    {
        $this->expectException(UrlGenerationException::class);
        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_SHOW);
    }

    public function testEdit(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_EDIT, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk()
            ->assertViewIs('form');
    }

    public function testEditWithNonExistingPetition(): void
    {
        $department = Department::factory()
            ->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_EDIT, [
                'department' => $department,
                'petition' => $this->faker->uuid,
            ])
            ->assertNotFound();
    }

    public function testUpdate(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_UPDATE,
                [
                    'department' => $department,
                    'petition' => $petition,
                ],
                [
                    'message' => $this->faker->word(),
                    'date_of_message' => $this->faker->date(),
                    'decision_reference' => $this->faker->word(),
                    'decision_date' => $this->faker->date(),
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ]);
    }

    public function testUpdateHtmx(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissionsAndDepartment(
            $department,
            Permission::PETITION_WRITE,
            Permission::PETITION_READ,
        )->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRouteAsHtmx(
                RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_UPDATE,
                [
                    'department' => $department,
                    'petition' => $petition,
                ],
                [
                    'hx-target' => 'hx-target',
                    'message' => $this->faker->word(),
                    'date_of_message' => $this->faker->date(),
                    'decision_reference' => $this->faker->word(),
                    'decision_date' => $this->faker->date(),
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertOk();
    }

    public function testUpdateWithNullDecisionFields(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_UPDATE,
                [
                    'department' => $department,
                    'petition' => $petition,
                ],
                [
                    'message' => $this->faker->word(),
                    'date_of_message' => $this->faker->date(),
                    'decision_reference' => null,
                    'decision_date' => null,
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ]);
    }

    public function testUpdateValidation(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_UPDATE,
                [
                    'department' => $department,
                    'petition' => $petition,
                ],
                [
                    'petition_id' => $petition->id->toString(),
                    'message' => '',
                    'date_of_message' => $this->faker->word(),
                    'decision_reference' => str_repeat('x', 65), // Over max length of 64
                    'decision_date' => $this->faker->word(),
                ],
            )
            ->assertSessionHasErrors([
                'message' => __('validation.custom.message.string'),
                'date_of_message' => __('validation.calendar_date'),
                'decision_reference' => __('validation.max.string', ['attribute' => 'decision reference', 'max' => 64]),
                'decision_date' => __('validation.calendar_date'),
            ]);
    }

    public function testUpdateValidationHtmx(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRouteAsHtmx(
                RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_UPDATE,
                [
                    'department' => $department,
                    'petition' => $petition,
                ],
                [
                    'hx-target' => 'hx-target',
                    'petition_id' => $petition->id->toString(),
                    'message' => 'FOOBAR',
                    'date_of_message' => $this->faker->word(),
                    'decision_date' => $this->faker->word(),
                ],
            )
            ->assertSessionHasErrors([
                'date_of_message' => __('validation.calendar_date'),
                'decision_date' => __('validation.calendar_date'),
            ]);
    }

    #[Test]
    public function testDownload(): void
    {
        $filename = $this->faker->word();
        $wordTemplateId = $this->faker->randomElement(WordTemplateId::cases());
        $disk = 'word_templates';

        ConfigHelper::set($disk, [
            'filesystem_disk' => $disk,
            'templates' => [
                $wordTemplateId->value => ['filename' => $filename],
            ],
        ]);

        Storage::fake($disk);
        Storage::disk($disk)->put($filename, $this->faker->word());

        $department = Department::factory()->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $this->mock(WordTemplateService::class, function (MockInterface $mock) use ($filename): void {
            $mock->expects('get')
                ->once()
                ->andReturn((object) [
                    'id' => $this->faker->uuid(),
                    'filename' => $filename,
                    'path' => $this->faker->word(),
                ]);
        });
        $this->mock(WordTemplateProcessingService::class, function (MockInterface $mock) use ($disk, $filename): void {
            $mock->expects('process')
                ->once()
                ->andReturn(Storage::disk($disk)->path($filename));
        });

        $authUser = User::factory()->withPermissionsAndDepartment(
            $department,
            Permission::PETITION_WRITE,
            Permission::PETITION_READ,
        )->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_WORD_TEMPLATE_DOWNLOAD, [
                'department' => $department,
                'petition' => $petition,
                'word_template_id' => $wordTemplateId,
            ])
            ->assertDownload($filename);
    }

    #[Test]
    public function testDownloadWhenPetitonNotFound(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissionsAndDepartment(
            $department,
            Permission::PETITION_WRITE,
            Permission::PETITION_READ,
        )->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_WORD_TEMPLATE_DOWNLOAD, [
                'department' => $department,
                'petition' => $this->faker->uuid(),
                'word_template_id' => $this->faker->randomElement(WordTemplateId::cases()),
            ])
            ->assertNotFound();
    }

    #[Test]
    public function testDownloadWhenWordTemplateNotFound(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $this->mock(WordTemplateService::class, function (MockInterface $mock): void {
            $mock->expects('get')
                ->once()
                ->andThrow(new WordTemplateException());
        });

        $authUser = User::factory()->withPermissionsAndDepartment(
            $department,
            Permission::PETITION_WRITE,
            Permission::PETITION_READ,
        )->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_WORD_TEMPLATE_DOWNLOAD, [
                'department' => $department,
                'petition' => $petition,
                'word_template_id' => $this->faker->randomElement(WordTemplateId::cases()),
            ])
            ->assertNotFound();
    }

    #[Test]
    public function testDownloadWhenWordTemplateProcessFails(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $this->mock(WordTemplateService::class, function (MockInterface $mock): void {
            $mock->expects('get')
                ->once()
                ->andReturn((object) [
                    'id' => $this->faker->uuid(),
                    'filename' => $this->faker->word(),
                    'path' => $this->faker->word(),
                ]);
        });
        $this->mock(WordTemplateProcessingService::class, function (MockInterface $mock): void {
            $mock->expects('process')
                ->once()
                ->andThrows(new WordTemplateProcessorException());
        });

        $authUser = User::factory()->withPermissionsAndDepartment(
            $department,
            Permission::PETITION_WRITE,
            Permission::PETITION_READ,
        )->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_WORD_TEMPLATE_DOWNLOAD, [
                'department' => $department,
                'petition' => $petition,
                'word_template_id' => $this->faker->randomElement(WordTemplateId::cases()),
            ])
            ->assertNotFound();
    }
}
