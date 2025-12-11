<?php

declare(strict_types=1);

namespace App\Actions\Terms;

use App\Actions\Petition\PetitionTermsUpdateAction;
use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\PetitionTerm;
use App\Models\User;
use Illuminate\Database\DatabaseManager;

readonly class PetitionTermDeleteAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
        private PetitionTermsUpdateAction $petitionTermsUpdateAction,
    ) {
    }

    public function execute(Petition $petition, PetitionTerm $petitionTerm, User $user): void
    {
        $this->databaseManager->transaction(function () use ($petitionTerm, $petition, $user): void {
            $this->deleteTermAndDescendants($petition, $petitionTerm, $user);
            $petition->refresh(); // make sure the petition is up to date with the deletions
            $this->petitionTermsUpdateAction->execute($petition);
        });
    }

    private function deleteTermAndDescendants(Petition $petition, PetitionTerm $petitionTerm, User $user): void
    {
        $petitionTerm->delete();

        $petition->timelineItems()->create([
            'user_id' => $user->id,
            'type' => TimelineType::TERM_DELETED,
            'data' => [
                'term_type' => $petitionTerm->type->value,
            ],
        ]);

        $petition->petitionTerms
            ->filter(static function ($item) use ($petitionTerm) {
                return $petitionTerm->id->equals($item->parent_id);
            })
            ->each(function (PetitionTerm $child) use ($petition, $user): void {
                $this->deleteTermAndDescendants($petition, $child, $user);
            });
    }
}
