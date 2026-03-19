<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Config\Config;
use Aws\S3\S3Client;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

use function sprintf;

/**
 * Command to create MinIO buckets for file storage and exports.
 *
 * This command connects to a MinIO instance using configured S3-compatible credentials
 * and creates the required storage bucket if it doesn't already exist.
 */
#[AsCommand(name: 'minio:setup', description: 'Create MinIO buckets for file storage')]
class CreateMinIOBucketsCommand extends Command
{
    public function __construct(private readonly ?S3Client $s3Client = null)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $s3Client = $this->s3Client ?? $this->createS3Client();
        $bucket = Config::string('filesystems.disks.minio.bucket');

        try {
            if ($s3Client->doesBucketExistV2($bucket)) {
                $this->info(sprintf("Bucket %s already exists.", $bucket));

                return self::SUCCESS;
            }

            $s3Client->createBucket(['Bucket' => $bucket]);
            $this->info(sprintf("Bucket %s created successfully.", $bucket));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error(sprintf("Error with bucket %s: %s", $bucket, $e->getMessage()));

            return self::FAILURE;
        }
    }

    protected function createS3Client(): S3Client
    {
        return new S3Client([
            'version' => 'latest',
            'region' => Config::string('filesystems.disks.minio.region'),
            'endpoint' => Config::string('filesystems.disks.minio.endpoint'),
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => Config::string('filesystems.disks.minio.key'),
                'secret' => Config::string('filesystems.disks.minio.secret'),
            ],
        ]);
    }
}
