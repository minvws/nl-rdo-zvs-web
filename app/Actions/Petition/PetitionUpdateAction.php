<?php

declare(strict_types=1);

namespace App\Actions\Petition;

use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\PetitionCategory;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Throwable;

readonly class PetitionUpdateAction
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
        if (!isset($attributes['name']) || $attributes['name'] === '') {
            $category = PetitionCategory::query()->find($attributes['petition_category_id']);
            $attributes['name'] = $category->name ?? '';
        }

        $this->databaseManager->transaction(static function () use ($petition, $user, $attributes): void {
            $previousTeamId = $petition->team_id;
            $petition->update($attributes);

            if (isset($attributes['team_id']) && $previousTeamId !== $attributes['team_id']) {
                $petition->refresh();
                $petition->timelineItems()->create([
                    'user_id' => $user->id,
                    'type' => TimelineType::TEAM_CHANGED,
                    'data' => new ArrayObject(['team' => $petition->team?->name]),
                ]);
            }

            $petition->timelineItems()->create([
                'user_id' => $user->id,
                'type' => TimelineType::PETITION_UPDATED,
                'data' => new ArrayObject($attributes),
            ]);
        });
    }
}
