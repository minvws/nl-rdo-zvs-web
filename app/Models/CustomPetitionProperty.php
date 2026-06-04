<?php

declare(strict_types=1);

namespace App\Models;

use App\Collections\CustomPetitionPropertyCollection;
use App\Enums\CustomPetitionPropertyType;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use Database\Factories\CustomPetitionPropertyFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Override;
use Ramsey\Uuid\UuidInterface;

/**
 * @property string $name
 * @property CustomPetitionPropertyType $type
 * @property int $ordering
 * @property int $grouping
 * @property UuidInterface $petition_type_id
 */
#[CollectedBy(CustomPetitionPropertyCollection::class)]
#[UseFactory(CustomPetitionPropertyFactory::class)]
#[Table('custom_petition_properties')]
class CustomPetitionProperty extends EloquentModel
{
    /** @use HasFactory<CustomPetitionPropertyFactory> */
    use HasFactory;
    use HasId;
    use HasTimestamps;

    /**
     * @return BelongsToMany<Petition, $this>
     */
    public function petitions(): BelongsToMany
    {
        return $this->belongsToMany(Petition::class, 'custom_petition_property_petition', 'custom_petition_property_id', 'petition_id');
    }

    /**
     * @return BelongsTo<PetitionType, $this>
     */
    public function petitionType(): BelongsTo
    {
        return $this->belongsTo(PetitionType::class);
    }

    /**
     * @return array<string, class-string<CustomPetitionPropertyType>|string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'type' => CustomPetitionPropertyType::class,
            'ordering' => 'int',
            'grouping' => 'int',
        ];
    }
}
