<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TimelineType;
use App\Models\Decision;
use App\Models\Petition;
use App\Models\TimelineItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Webmozart\Assert\Assert;

/**
 * @extends Factory<TimelineItem>
 */
class TimelineItemFactory extends Factory
{
    /** @var class-string<TimelineItem> $model */
    protected $model = TimelineItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $definition = [
            'timelineable_id' => Petition::factory(),
            'timelineable_type' => 'petition',
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(TimelineType::cases()),
            'created_at' => $this->faker->dateTimeBetween('-1 year'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year'),
        ];

        $timelineableModel = $this->findRecycledTimelineableModel();
        if ($timelineableModel) {
            $definition['timelineable_id'] = $timelineableModel->getKey();
            $definition['timelineable_type'] = $this->getTimelineableType($timelineableModel);
        }

        return $definition;
    }

    private function findRecycledTimelineableModel(): ?Model
    {
        foreach ($this->recycle as $modelClass => $collection) {
            if ($modelClass === Decision::class || $modelClass === Petition::class) {
                Assert::isInstanceOf($collection, Collection::class);

                $firstModel = $collection->first();
                if ($firstModel instanceof Model) {
                    return $firstModel;
                }
            }
        }

        return null;
    }

    private function getTimelineableType(Model $model): string
    {
        return match ($model::class) {
            Decision::class => 'decision',
            Petition::class => 'petition',
            default => 'petition',
        };
    }
}
