<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExternalUrlType;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use Database\Factories\PetitionExternalUrlFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\AsUri;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Uri;

/**
 * @property ExternalUrlType $petition_external_url_type
 * @property Uri $url
 */

#[UseFactory(PetitionExternalUrlFactory::class)]
class PetitionExternalUrl extends EloquentModel
{
    /** @use HasFactory<PetitionExternalUrlFactory> */
    use HasFactory;
    use HasId;
    use HasTimestamps;

    protected $table = 'petition_external_urls';

    /**
     * @return BelongsTo<Petition, $this>
     */
    public function petition(): BelongsTo
    {
        return $this->belongsTo(Petition::class);
    }

    protected function casts(): array
    {
        return [
            'petition_external_url_type' => ExternalUrlType::class,
            'url' => AsUri::class,
        ];
    }
}
