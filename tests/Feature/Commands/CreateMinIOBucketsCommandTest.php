<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Config\Config;
use App\Console\Commands\CreateMinIOBucketsCommand;
use Aws\S3\S3Client;
use Exception;
use ReflectionClass;
use Tests\Feature\FeatureTestCase;

use function sprintf;

class CreateMinIOBucketsCommandTest extends FeatureTestCase
{
    public function testCommandCreatesNewBucket(): void
    {
        $bucketName = Config::string('filesystems.disks.minio.bucket');

        $s3Client = $this->mock(S3Client::class);
        $s3Client->expects('doesBucketExistV2')
            ->with($bucketName)
            ->andReturn(false);

        $s3Client->expects('createBucket')
            ->with(['Bucket' => $bucketName])
            ->andReturn([]);

        $this->artisan('minio:setup')
            ->expectsOutput(sprintf("Bucket %s created successfully.", $bucketName))
            ->assertSuccessful();
    }

    public function testCommandHandlesExistingBucket(): void
    {
        $bucketName = Config::string('filesystems.disks.minio.bucket');

        $s3Client = $this->mock(S3Client::class);
        $s3Client->expects('doesBucketExistV2')
            ->with($bucketName)
            ->andReturn(true);

        $this->artisan('minio:setup')
            ->expectsOutput(sprintf("Bucket %s already exists.", $bucketName))
            ->assertSuccessful();
    }

    public function testCommandHandlesErrors(): void
    {
        $bucketName = Config::string('filesystems.disks.minio.bucket');

        $s3Client = $this->mock(S3Client::class);
        $s3Client->expects('doesBucketExistV2')
            ->with($bucketName)
            ->andThrow(new Exception('Connection error'));

        $this->artisan('minio:setup')
            ->expectsOutput(sprintf("Error with bucket %s: Connection error", $bucketName))
            ->assertFailed();
    }

    public function testCreateS3ClientWithCorrectConfiguration(): void
    {
        $command = new CreateMinIOBucketsCommand();
        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('createS3Client');
        $method->setAccessible(true);

        $s3Client = $method->invoke($command);

        $this->assertInstanceOf(S3Client::class, $s3Client);
        $this->assertEquals(Config::string('filesystems.disks.minio.region'), $s3Client->getRegion());
        $this->assertEquals(Config::string('filesystems.disks.minio.endpoint'), $s3Client->getEndpoint());
    }
}
