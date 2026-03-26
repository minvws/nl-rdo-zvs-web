<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Config\Config;
use Aws\S3\S3Client;
use Exception;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;
use stdClass;
use Tests\Feature\FeatureTestCase;

use function config;
use function sprintf;

class CreateMinIOBucketsCommandTest extends FeatureTestCase
{
    public function testCommandCreatesNewBucket(): void
    {
        [$uploadsBucket, $exportsBucket] = $this->configureBuckets();

        $uploadsClient = $this->mock(S3Client::class);
        $exportsClient = $this->mock(S3Client::class);

        $this->mockS3Disk('uploads', $uploadsClient);
        $this->mockS3Disk('exports', $exportsClient);

        $uploadsClient->expects('doesBucketExistV2')->with($uploadsBucket)->andReturn(false);
        $uploadsClient->expects('createBucket')->with(['Bucket' => $uploadsBucket])->andReturn([]);

        $exportsClient->expects('doesBucketExistV2')->with($exportsBucket)->andReturn(false);
        $exportsClient->expects('createBucket')->with(['Bucket' => $exportsBucket])->andReturn([]);

        $this->artisan('minio:setup')
            ->expectsOutput(sprintf('Bucket %s created successfully.', $uploadsBucket))
            ->expectsOutput(sprintf('Bucket %s created successfully.', $exportsBucket))
            ->assertSuccessful();
    }

    public function testCommandHandlesExistingBucket(): void
    {
        [$uploadsBucket, $exportsBucket] = $this->configureBuckets();

        $uploadsClient = $this->mock(S3Client::class);
        $exportsClient = $this->mock(S3Client::class);

        $this->mockS3Disk('uploads', $uploadsClient);
        $this->mockS3Disk('exports', $exportsClient);

        $uploadsClient->expects('doesBucketExistV2')->with($uploadsBucket)->andReturn(true);
        $exportsClient->expects('doesBucketExistV2')->with($exportsBucket)->andReturn(true);

        $this->artisan('minio:setup')
            ->expectsOutput(sprintf('Bucket %s already exists.', $uploadsBucket))
            ->expectsOutput(sprintf('Bucket %s already exists.', $exportsBucket))
            ->assertSuccessful();
    }

    public function testCommandHandlesErrors(): void
    {
        [$uploadsBucket] = $this->configureBuckets();

        $uploadsClient = $this->mock(S3Client::class);
        $this->mockS3Disk('uploads', $uploadsClient);

        $uploadsClient->expects('doesBucketExistV2')
            ->with($uploadsBucket)
            ->andThrow(new Exception('Connection error'));

        $this->artisan('minio:setup')
            ->expectsOutput(sprintf('Error with bucket %s: Connection error', $uploadsBucket))
            ->assertFailed();
    }

    public function testCommandFailsWhenDiskIsNotS3Compatible(): void
    {
        $this->configureBuckets();

        $nonS3Disk = new stdClass();
        Storage::shouldReceive('disk')->with('uploads')->once()->andReturn($nonS3Disk);

        $this->artisan('minio:setup')
            ->expectsOutput('Disk uploads does not expose an S3 client.')
            ->assertFailed();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function configureBuckets(): array
    {
        config()->set('filesystems.disks.uploads.bucket', 'uploads-test-bucket');
        config()->set('filesystems.disks.exports.bucket', 'exports-test-bucket');

        return [
            Config::string('filesystems.disks.uploads.bucket'),
            Config::string('filesystems.disks.exports.bucket'),
        ];
    }

    private function mockS3Disk(string $diskName, object $s3Client): void
    {
        $disk = $this->mock(AwsS3V3Adapter::class);
        $disk->expects('getClient')->andReturn($s3Client);

        Storage::shouldReceive('disk')->with($diskName)->once()->andReturn($disk);
    }
}
