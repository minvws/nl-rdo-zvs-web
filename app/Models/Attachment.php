<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasId;
use Database\Factories\AttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface $id
 * @property string $disk
 * @property string $path
 * @property string $name
 */
#[Table('attachments')]
#[UseFactory(AttachmentFactory::class)]
class Attachment extends EloquentModel
{
    /** @use HasFactory<AttachmentFactory> */
    use HasFactory;
    use HasId;
}
