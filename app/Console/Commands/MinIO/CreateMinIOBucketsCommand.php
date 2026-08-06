<?php

declare(strict_types=1);

namespace App\Console\Commands\MinIO;

use App\Config\Config;
use Aws\S3\S3Client;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\AwsS3V3Adapter as LaravelAwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;
use Throwable;

use function sprintf;

/**
 * Command to create MinIO buckets for file storage and exports.
 *
 * This command connects to a MinIO instance using configured S3-compatible credentials
 * and creates the required storage bucket if it doesn't already exist.
 */
#[Description('Create MinIO buckets for file storage')]
#[Signature('minio:setup')]
class CreateMinIOBucketsCommand extends Command
{
    private const array DISKS = ['uploads', 'exports'];

    public function handle(): int
    {
        foreach (self::DISKS as $diskName) {
            $bucket = Config::string(sprintf('filesystems.disks.%s.bucket', $diskName));
            $disk = Storage::disk($diskName);

            if (!$disk instanceof LaravelAwsS3V3Adapter) {
                $this->error(sprintf('Disk %s does not expose an S3 client.', $diskName));

                return self::FAILURE;
            }

            /** @var S3Client $s3Client */
            $s3Client = $disk->getClient();

            try {
                if ($s3Client->doesBucketExistV2($bucket)) {
                    $this->info(sprintf('Bucket %s already exists.', $bucket));

                    continue;
                }

                $s3Client->createBucket(['Bucket' => $bucket]);
                $this->info(sprintf('Bucket %s created successfully.', $bucket));
            } catch (Throwable $e) {
                $this->error(sprintf('Error with bucket %s: %s', $bucket, $e->getMessage()));

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
