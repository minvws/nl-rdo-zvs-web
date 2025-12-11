<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Timelineable;

use App\Enums\Authorization\DepartmentRole;
use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Enums\TimelineType;
use App\Models\Attachment;
use App\Models\Decision;
use App\Models\Department;
use App\Models\Petition;
use App\Models\TimelineItem;
use App\Models\User;
use App\Repositories\RepositoryTransactionException;
use App\Repositories\RepositoryTransactionInterface;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Middleware\ValidatePostSize;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\ConfigHelper;

use function json_encode;
use function route;
use function sprintf;

class TimelineableNoteControllerTest extends FeatureTestCase
{
    public function testCreate(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $user = User::factory()
            ->fullyVerified()
            ->withDepartmentRoles($department, DepartmentRole::WRITE)
            ->create();

        $url = Crypt::encryptString(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]));

        $this->beUser($user, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_CREATE, [
                'department' => $department,
                'timelineableType' => $petition->getMorphClass(),
                'timelineable' => $petition,
                'url' => $url,
            ])
            ->assertOk();
    }

    public function testStore(): void
    {
        $comment = $this->faker->sentence();
        $department = Department::factory()->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $user = User::factory()
            ->fullyVerified()
            ->withDepartmentRoles($department, DepartmentRole::WRITE)
            ->create();

        $url = Crypt::encryptString(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]));

        $this->beUser($user, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_STORE, [
                'department' => $department,
                'timelineableType' => $petition->getMorphClass(),
                'timelineable' => $petition,
                'url' => $url,
            ], [
                'comment' => $comment,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(TimelineItem::class, [
            'type' => TimelineType::NOTE,
            'data' => json_encode(['comment' => $comment, 'attachmentIds' => []]),
        ]);
    }

    public function testStoreWithAttachment(): void
    {
        $comment = $this->faker->sentence();
        $disk = 'uploads';
        $imageExtension = $this->faker->randomElement(['jpg', 'gif', 'png']);

        ConfigHelper::set(sprintf('filesystems.disks.%s.allowed_extensions', $disk), [$imageExtension]);

        $department = Department::factory()->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        Storage::fake($disk);
        $filename = sprintf('%s.%s', $this->faker->word(), $imageExtension);
        $file = UploadedFile::fake()->image($filename);

        $user = User::factory()
            ->fullyVerified()
            ->withDepartmentRoles($department, DepartmentRole::WRITE)
            ->create();

        $url = Crypt::encryptString(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]));

        $this->beUser($user, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_CREATE, [
                'department' => $department,
                'timelineableType' => $petition->getMorphClass(),
                'timelineable' => $petition,
                'url' => $url,
            ], [
                'comment' => $comment,
                'attachments' => [$file],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseCount(Attachment::class, 1);
        $this->assertDatabaseHas(Attachment::class, [
            'disk' => $disk,
            'name' => $filename,
        ]);

        $attachment = Attachment::firstOrFail();
        $this->assertDatabaseHas(TimelineItem::class, [
            'type' => TimelineType::NOTE,
            'data' => json_encode(['comment' => $comment, 'attachmentIds' => [$attachment->id]]),
        ]);
    }

    public function testStoreWithInvalidAttachment(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $url = Crypt::encryptString(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]));

        $user = User::factory()
            ->fullyVerified()
            ->withDepartmentRoles($department, DepartmentRole::WRITE)
            ->create();

        $this->beUser($user, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_CREATE, [
                'department' => $department,
                'timelineableType' => $petition->getMorphClass(),
                'timelineable' => $petition,
                'url' => $url,
            ], [
                'comment' => $this->faker->sentence(),
                'attachments' => [$this->faker->word()], // string instead of uploaded file
            ])
//            ->assertSessionHasErrors('attachments.0')
            ->assertSessionHasErrors()
            ->assertRedirect();
    }

    public function testStoreWithUnallowedAttachment(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $disk = 'uploads';
        ConfigHelper::set(sprintf('filesystems.disks.%s.allowed_extensions', $disk), ['invalid']);

        Storage::fake($disk);
        $filename = sprintf('%s.%s', $this->faker->word(), $this->faker->unique()->fileExtension());
        $file = UploadedFile::fake()->image($filename);

        $url = Crypt::encryptString(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]));

        $user = User::factory()
            ->fullyVerified()
            ->withDepartmentRoles($department, DepartmentRole::WRITE)
            ->create();

        $this->beUser($user, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_CREATE, [
                'department' => $department,
                'timelineableType' => $petition->getMorphClass(),
                'timelineable' => $petition,
                'url' => $url,
            ], [
                'comment' => $this->faker->sentence(),
                'attachments' => [$file],
            ])
            ->assertSessionHasErrors('attachments.0');
    }

    public function testStoreTransactionFails(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $this->mock(RepositoryTransactionInterface::class, static function (MockInterface $mock): void {
            $mock->expects('transaction')
                ->once()
                ->andThrows(new RepositoryTransactionException());
        });

        $url = Crypt::encryptString(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]));

        $authUser = User::factory()
            ->fullyVerified()
            ->withDepartmentRoles($department, DepartmentRole::WRITE)
            ->create();

        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_CREATE, [
                'department' => $department,
                'timelineableType' => $petition->getMorphClass(),
                'timelineable' => $petition,
                'url' => $url,
            ], [
                'comment' => $this->faker->sentence(),
            ])
            ->assertServerError();
    }

    public function testStoreWithInvalidPermission(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $url = Crypt::encryptString(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]));

        $authUser = User::factory()->withPermissionsAndDepartment($department)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_CREATE, [
                'department' => $department,
                'timelineableType' => $petition->getMorphClass(),
                'timelineable' => $petition,
                'url' => $url,
            ], [
                'comment' => $this->faker->sentence(),
            ])
            ->assertForbidden();
    }

    #[Test]
    public function testStoreWithTooBigAttachment(): void
    {
        $this->mock(ValidatePostSize::class, function ($mock): void {
            $mock->shouldReceive('handle')->andThrow(new PostTooLargeException());
        });

        $department = Department::factory()->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $url = Crypt::encryptString(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]));

        $user = User::factory()
            ->fullyVerified()
            ->withDepartmentRoles($department, DepartmentRole::WRITE)
            ->create();

        $this->beUser($user, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_CREATE, [
                'department' => $department,
                'timelineableType' => $petition->getMorphClass(),
                'timelineable' => $petition,
                'url' => $url,
            ], [
                'comment' => $this->faker->sentence(),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['message' => 'validation.upload_too_big']);
    }

    #[Test]
    public function testCanCreateNoteForNonArchivedPetition(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create([
            'archived_at' => null,
        ]);

        $encryptedUrl = Crypt::encryptString(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]));

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_CREATE, [
                'department' => $department,
                'timelineableType' => 'petition',
                'timelineable' => $petition->id,
                'url' => $encryptedUrl,
            ])
            ->assertOk();
    }

    #[Test]
    public function testCannotCreateNoteForArchivedPetition(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create([
            'archived_at' => Carbon::now(),
        ]);

        $encryptedUrl = Crypt::encryptString(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]));

        $user = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();

        $this->beUser($user, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_CREATE, [
                'department' => $department,
                'timelineableType' => 'petition',
                'timelineable' => $petition->id,
                'url' => $encryptedUrl,
            ])
            ->assertForbidden();
    }

    #[Test]
    public function testCanCreateNoteForDecision(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create([]);

        $encryptedUrl = Crypt::encryptString(route(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
            'department' => $department,
            'decision' => $decision,
        ]));

        $user = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_WRITE)->fullyVerified()->create();

        $this->beUser($user, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_CREATE, [
                'department' => $department,
                'timelineableType' => 'decision',
                'timelineable' => $decision->id,
                'url' => $encryptedUrl,
            ])
            ->assertOk();
    }

    #[Test]
    public function testCannotStoreNoteForArchivedPetition(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create([
            'archived_at' => Carbon::now(),
        ]);

        $encryptedUrl = Crypt::encryptString(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]));

        $user = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();

        $this->beUser($user, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_STORE, [
                'department' => $department,
                'timelineableType' => 'petition',
                'timelineable' => $petition->id,
                'url' => $encryptedUrl,
            ], [
                'comment' => 'Test note content',
            ])
            ->assertForbidden();
    }

    #[Test]
    public function testHtmxValidationErrorsRedirectBackToCreateForm(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $url = Crypt::encryptString(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]));

        $user = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();

        // Submit via htmx with empty comment (should fail validation)
        $response = $this->beUser($user, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_STORE, [
                'department' => $department,
                'timelineableType' => $petition->getMorphClass(),
                'timelineable' => $petition,
                'url' => $url,
            ], [
                'comment' => '', // Empty comment should fail validation
            ]);

        // Should redirect back to create form with validation errors
        $response->assertRedirect();
        $response->assertSessionHasErrors('comment');
    }

    #[Test]
    public function testStoreWithHtmxRequest(): void
    {
        $comment = $this->faker->sentence();
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $url = Crypt::encryptString(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]));

        $user = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();

        // Submit via htmx (add HX-Request header)
        $response = $this->beUser($user, true, $department)
            ->withHeaders(['HX-Request' => 'true'])
            ->postByRoute(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_STORE, [
                'department' => $department,
                'timelineableType' => $petition->getMorphClass(),
                'timelineable' => $petition,
                'url' => $url,
            ], [
                'comment' => $comment,
            ]);

        $response->assertOk();
        $response->assertHeader('HX-Trigger', 'eventPetitionUpdated-timeline');

        $this->assertDatabaseHas(TimelineItem::class, [
            'type' => TimelineType::NOTE,
            'data' => json_encode(['comment' => $comment, 'attachmentIds' => []]),
        ]);
    }

    #[Test]
    public function testHtmxValidationErrorsWithHtmxTarget(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $url = Crypt::encryptString(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]));

        $user = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();

        // Submit via htmx with hx-target field and empty comment (should fail validation)
        $response = $this->beUser($user, true, $department)
            ->withHeaders(['HX-Request' => 'true'])
            ->postByRoute(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_STORE, [
                'department' => $department,
                'timelineableType' => $petition->getMorphClass(),
                'timelineable' => $petition,
                'url' => $url,
            ], [
                'comment' => '',
                'hx-target' => 'notes-block',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('comment');

        $expectedUrl = route(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_CREATE, [
            'department' => $department,
            'timelineableType' => $petition->getMorphClass(),
            'timelineable' => $petition,
            'url' => $url,
            'hx-target' => 'notes-block',
        ]);
        $response->assertRedirect($expectedUrl);
    }
}
