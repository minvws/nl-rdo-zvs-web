<?php

declare(strict_types=1);

namespace App\Actions\ExternalUrl;

use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Webmozart\Assert\Assert;

use function collect;
use function in_array;
use function trim;

readonly class ExternalUrlUpdateAction
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
            $petition->externalUrls()->delete();
            $this->createNewExternalUrls($attributes, $petition);
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
            'type' => TimelineType::EXTERNAL_URL_UPDATED,
            'data' => $attributes,
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createNewExternalUrls(array $attributes, Petition $petition): void
    {
        Assert::keyExists($attributes, 'external_urls');
        Assert::isArray($attributes['external_urls']);

        /** @var array<array<string, mixed>> $externalUrlsData */
        $externalUrlsData = collect($attributes['external_urls'])
            ->filter(static function (mixed $item): bool {
                Assert::isArray($item);
                Assert::keyExists($item, 'url');
                Assert::nullOrString($item['url']);

                return !in_array(trim((string) $item['url']), ['', '0'], true);
            })
            ->map(static function (mixed $item) use ($petition): array {
                Assert::isArray($item);
                Assert::keyExists($item, 'petition_external_url_type');
                Assert::keyExists($item, 'url');

                return [
                    'petition_id' => $petition->id,
                    'petition_external_url_type' => $item['petition_external_url_type'],
                    'url' => $item['url'],
                ];
            })
            ->all();

        if (!empty($externalUrlsData)) {
            $petition->externalUrls()->createMany($externalUrlsData);
        }
    }
}
