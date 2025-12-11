<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\Authorization\Permission;
use App\Models\Attachment;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\FeatureTestCase;

use function sprintf;

class AttachmentControllerTest extends FeatureTestCase
{
    public function testDownload(): void
    {
        $disk = 'uploads';

        Storage::fake($disk);
        $filename = sprintf('%s.%s', $this->faker->word(), $this->faker->fileExtension());
        Storage::disk($disk)->put($filename, 'foo');

        $attachment = Attachment::factory()
            ->create([
                'disk' => $disk,
                'path' => $filename,
                'name' => $filename,
            ]);

        $user = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($user)
            ->getByRoute('attachments.download', ['attachment' => $attachment->id])
            ->assertSessionHasNoErrors()
            ->assertDownload($filename);
    }

    public function testDownloadNotFound(): void
    {
        $user = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($user)
            ->getByRoute('attachments.download', ['attachment' => $this->faker->uuid()])
            ->assertNotFound();
    }

    public function testDownloadWithInvalidPermission(): void
    {
        $attachment = Attachment::factory()
            ->create();

        $user = User::factory()->fullyVerified()->create();
        $this->beUser($user)
            ->getByRoute('attachments.download', ['attachment' => $attachment->id])
            ->assertForbidden();
    }
}
