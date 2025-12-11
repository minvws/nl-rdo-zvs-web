<?php

declare(strict_types=1);

namespace App\Actions\Terms;

use App\Actions\Petition\PetitionTermsUpdateAction;
use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\PetitionDraftTerm;
use App\Models\User;
use Illuminate\Database\DatabaseManager;

readonly class PetitionDraftTermDeleteAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
        private PetitionTermsUpdateAction $petitionTermsUpdateAction,
    ) {
    }

    public function execute(Petition $petition, PetitionDraftTerm $draftTerm, User $user): void
    {
        $this->databaseManager->transaction(function () use ($petition, $draftTerm, $user): void {
            $draftTerm->delete();

            $petition->timelineItems()->create([
                'user_id' => $user->id,
                'type' => TimelineType::DRAFT_TERM_DELETED,
            ]);

            $this->petitionTermsUpdateAction->execute($petition);
        });
    }
}
