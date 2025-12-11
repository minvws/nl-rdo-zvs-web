<?php

declare(strict_types=1);

namespace App\Actions\Petition;

use App\Enums\Occurrence;
use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Query\Builder;

readonly class PetitionDetachAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    public function execute(Petition $petition, Petition $relatedPetition, User $user): void
    {
        $this->databaseManager->transaction(function () use ($petition, $relatedPetition, $user): void {
            $this->databaseManager
                ->table('petition_petition')
                ->where(static function (Builder $query) use ($petition, $relatedPetition): void {
                    $query->where('petition_id', $petition->id)
                        ->where('related_petition_id', $relatedPetition->id);
                })
                ->orWhere(static function (Builder $query) use ($petition, $relatedPetition): void {
                    $query->where('petition_id', $relatedPetition->id)
                        ->where('related_petition_id', $petition->id);
                })
                ->delete();

            $petition->timelineItems()->create([
                'type' => TimelineType::REFERENCED_OCCURRENCE,
                'user_id' => $user->id,
                'data' => new ArrayObject([
                    'type' => Occurrence::PETITION_TYPE->value,
                    'action' => Occurrence::DETACH_ACTION->value,
                    'subject' => $relatedPetition->number,
                ]),
            ]);
            $relatedPetition->timelineItems()->create([
                'type' => TimelineType::REFERENCED_OCCURRENCE,
                'user_id' => $user->id,
                'data' => new ArrayObject([
                    'type' => Occurrence::PETITION_TYPE->value,
                    'action' => Occurrence::DETACH_ACTION->value,
                    'subject' => $petition->number,
                ]),
            ]);
        });
    }
}
