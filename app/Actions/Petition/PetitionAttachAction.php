<?php

declare(strict_types=1);

namespace App\Actions\Petition;

use App\Enums\Occurrence;
use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Throwable;

readonly class PetitionAttachAction
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
        $relatedPetition = Petition::query()->where('number', $attributes['number'])->firstOrFail();

        $this->databaseManager->transaction(function () use ($petition, $relatedPetition, $user): void {
            $this->databaseManager
                ->table('petition_petition')
                ->updateOrInsert([
                    'petition_id' => $petition->id,
                    'related_petition_id' => $relatedPetition->id,
                ]);

            $petition->timelineItems()->create([
                'type' => TimelineType::REFERENCED_OCCURRENCE,
                'user_id' => $user->id,
                'data' => new ArrayObject([
                    'type' => Occurrence::PETITION_TYPE->value,
                    'action' => Occurrence::ATTACH_ACTION->value,
                    'subject' => $relatedPetition->number,
                ]),
            ]);

            $relatedPetition->timelineItems()->create([
                'type' => TimelineType::REFERENCED_OCCURRENCE,
                'user_id' => $user->id,
                'data' => new ArrayObject([
                    'type' => Occurrence::PETITION_TYPE->value,
                    'action' => Occurrence::ATTACH_ACTION->value,
                    'subject' => $petition->number,
                ]),
            ]);
        });
    }
}
