<?php

declare(strict_types=1);

namespace App\Actions\PetitionCustomProperties;

use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Throwable;
use Webmozart\Assert\Assert;

use function array_key_exists;

readonly class PetitionCustomPetitionPropertiesUpdateAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @throws Throwable
     */
    public function execute(Petition $petition, User $user, array $attributes): void
    {
        $this->databaseManager->transaction(static function () use ($petition, $user, $attributes): void {
            if (!array_key_exists('custom_petition_properties', $attributes)) {
                $attributes['custom_petition_properties'] = [];
            }

            $petition->timelineItems()->create([
                'user_id' => $user->id,
                'type' => TimelineType::PETITION_CUSTOM_PROPERTIES_CHANGED,
                'data' => new ArrayObject($attributes),
            ]);

            $customPetitionProperties = $attributes['custom_petition_properties'];
            Assert::isArray($customPetitionProperties);
            Assert::allString($customPetitionProperties);
            Assert::allUuid($customPetitionProperties);

            $petition->customPetitionProperties()
                ->sync($customPetitionProperties);
        });
    }
}
