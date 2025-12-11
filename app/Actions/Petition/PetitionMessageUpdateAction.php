<?php

declare(strict_types=1);

namespace App\Actions\Petition;

use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Database\DatabaseManager;

class PetitionMessageUpdateAction
{
    public function __construct(
        private readonly DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function execute(Petition $petition, array $attributes, User $user): void
    {
        $this->databaseManager->transaction(static function () use ($petition, $attributes, $user): void {
            $petition->update($attributes);
            $petition->timelineItems()->create([
                'user_id' => $user->id,
                'type' => TimelineType::CORRESPONDENCE_UPDATED,
                'data' => $attributes,
            ]);
        });
    }
}
