<?php

declare(strict_types=1);

namespace App\Actions\CustomCost;

use App\Enums\TimelineType;
use App\Models\CustomCost;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Webmozart\Assert\Assert;

use function collect;
use function round;

readonly class CustomCostUpdateAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function execute(Petition $petition, User $user, array $attributes): void
    {
        $this->databaseManager->transaction(function () use ($petition, $attributes, $user): void {
            $this->deleteAllExistingCustomCostsForPetition($petition->customCosts);
            $this->createNewCustomCosts($attributes, $petition);
            $this->createTimelineItem($petition, $attributes, $user);
        });
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createTimelineItem(Petition $petition, array $attributes, User $user): void
    {
        $petition->timelineItems()->create([
            'user_id' => $user->id,
            'type' => TimelineType::CUSTOM_COST_UPDATED,
            'data' => $attributes,
        ]);
    }

    /**
     * @param Collection<int, CustomCost> $currentCustomCosts
     */
    private function deleteAllExistingCustomCostsForPetition(Collection $currentCustomCosts): void
    {
        $currentCustomCosts->each(function (CustomCost $customCost): self {
            $customCost->delete();

            return $this;
        });
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createNewCustomCosts(array $attributes, Petition $petition): void
    {
        Assert::keyExists($attributes, 'custom_costs');
        Assert::isArray($attributes['custom_costs']);

        collect($attributes['custom_costs'])
            ->filter(static function (mixed $item): bool {
                Assert::isArray($item);
                Assert::keyExists($item, 'custom_cost_amount_in_euros');
                Assert::numeric($item['custom_cost_amount_in_euros']);

                return (float) $item['custom_cost_amount_in_euros'] > 0;
            })
            ->each(function (mixed $item) use ($petition): self {
                Assert::isArray($item);
                Assert::keyExists($item, 'custom_cost_type');
                Assert::keyExists($item, 'custom_cost_amount_in_euros');
                Assert::numeric($item['custom_cost_amount_in_euros']);
                $amountInCents = (int) round((float) $item['custom_cost_amount_in_euros'] * 100);

                CustomCost::query()->create([
                    'petition_id' => $petition->id,
                    'custom_cost_type' => $item['custom_cost_type'],
                    'custom_cost_amount_in_cents' => $amountInCents,
                ]);

                return $this;
            });
    }
}
