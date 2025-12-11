<?php

declare(strict_types=1);

namespace App\Actions\Petition;

use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Webmozart\Assert\Assert;

use function collect;
use function in_array;
use function trim;

readonly class QuerysnapshotUpdateAction
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
            $petition->querysnapshots()->delete();
            $this->createNewQuerysnapshots($attributes, $petition);
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
            'type' => TimelineType::QUERYSNAPSHOT_UPDATED,
            'data' => $attributes,
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createNewQuerysnapshots(array $attributes, Petition $petition): void
    {
        Assert::keyExists($attributes, 'querysnapshots');
        Assert::isArray($attributes['querysnapshots']);

        /** @var array<array<string, mixed>> $querysnapshotsData */
        $querysnapshotsData = collect($attributes['querysnapshots'])
            ->filter(static function (mixed $item): bool {
                Assert::isArray($item);
                Assert::keyExists($item, 'querysnapshot_id');
                Assert::nullOrString($item['querysnapshot_id']);

                return !in_array(trim((string) $item['querysnapshot_id']), ['', '0'], true);
            })
            ->map(static function (mixed $item) use ($petition): array {
                Assert::isArray($item);
                Assert::keyExists($item, 'querysnapshot_type');
                Assert::keyExists($item, 'querysnapshot_id');

                return [
                    'petition_id' => $petition->id,
                    'querysnapshot_type' => $item['querysnapshot_type'],
                    'querysnapshot_id' => $item['querysnapshot_id'],
                ];
            })
            ->all();

        if (!empty($querysnapshotsData)) {
            $petition->querysnapshots()->createMany($querysnapshotsData);
        }
    }
}
