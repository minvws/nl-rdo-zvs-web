<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CustomCostType;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use Database\Factories\CustomCostFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property CustomCostType $custom_cost_type
 * @property int $custom_cost_amount_in_cents
 * @property float $amountInEuros
 *
 * @property-read Petition $petition
 */

#[UseFactory(CustomCostFactory::class)]
#[Table('custom_costs')]
class CustomCost extends EloquentModel
{
    /** @use HasFactory<CustomCostFactory> */
    use HasFactory;
    use HasId;
    use HasTimestamps;

    /**
     * @return BelongsTo<Petition, $this>
     */
    public function petition(): BelongsTo
    {
        return $this->belongsTo(Petition::class, 'petition_id');
    }

    /**
     * @return array<string, class-string<CustomCostType>|string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'custom_cost_type' => CustomCostType::class,
            'custom_cost_amount_in_cents' => 'integer',
        ];
    }

    /**
     * @return Attribute<int, CustomCostType>
     */
    protected function amountInEuros(): Attribute
    {
        return Attribute::make(
            get: fn(): int|float => $this->custom_cost_amount_in_cents / 100,
            set: static fn(float $value): float => $value * 100,
        );
    }
}
