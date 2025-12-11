<?php

declare(strict_types=1);

namespace App\Actions\Petition;

use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Throwable;

readonly class PetitionArchiveAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function execute(Petition $petition, User $user): void
    {
        $this->databaseManager->transaction(static function () use ($petition, $user): void {
            $petition->update(['archived_at' => CarbonImmutable::now()]);

            $petition->timelineItems()->create([
                'type' => TimelineType::PETITION_ARCHIVED,
                'user_id' => $user->id,
                'data' => null,
            ]);
        });
    }
}
