<?php

declare(strict_types=1);

namespace App\Actions\Terms;

use App\Actions\Petition\PetitionTermsUpdateAction;
use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\PetitionDraftTerm;
use App\Models\User;
use App\Services\Terms\DraftTermToPetitionTermsService;
use Illuminate\Database\DatabaseManager;

readonly class PetitionDraftTermUpdateAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
        private DraftTermToPetitionTermsService $draftTermToPetitionTermsService,
        private PetitionTermsUpdateAction $petitionTermsUpdateAction,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function execute(Petition $petition, PetitionDraftTerm $draftTerm, User $user, array $attributes): void
    {
        $this->databaseManager->transaction(function () use ($petition, $draftTerm, $user, $attributes): void {
            $draftTerm->update($attributes);

            $petition->timelineItems()->create([
                'user_id' => $user->id,
                'type' => TimelineType::DRAFT_TERM_UPDATED,
            ]);

            $this->draftTermToPetitionTermsService->convertDraftTermToPetitionTerms($draftTerm, $user);
            $this->petitionTermsUpdateAction->execute($petition);
        });
    }
}
