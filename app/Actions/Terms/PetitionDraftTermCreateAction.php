<?php

declare(strict_types=1);

namespace App\Actions\Terms;

use App\Actions\Petition\PetitionTermsUpdateAction;
use App\Enums\TimelineType;
use App\Exception\DomainException;
use App\Models\Petition;
use App\Models\PetitionDraftTerm;
use App\Models\User;
use App\Services\Terms\DraftTermToPetitionTermsService;
use Illuminate\Database\DatabaseManager;

readonly class PetitionDraftTermCreateAction
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
    public function execute(Petition $petition, User $user, array $attributes): PetitionDraftTerm
    {
        return $this->databaseManager->transaction(function () use ($petition, $user, $attributes): PetitionDraftTerm {
            $latestTermEndDate = $petition->petitionTerms->latestEndDate();
            if ($latestTermEndDate === null) {
                throw new DomainException('Cannot create draft term: petition must have at least one existing term');
            }

            $startDate = $latestTermEndDate->addDay();

            $draftTerm = new PetitionDraftTerm([

                'petition_id' => $petition->id,
                'start_date' => $startDate,
                ...$attributes,
            ]);

            $petition->timelineItems()->create([
                'user_id' => $user->id,
                'type' => TimelineType::DRAFT_TERM_CREATED,
            ]);

            $willBeConvertedImmediately = $draftTerm->event_date !== null || $draftTerm->date_withdrawal !== null;

            if ($willBeConvertedImmediately) {
                $this->draftTermToPetitionTermsService->convertDraftTermToPetitionTerms($draftTerm, $user);

                return $draftTerm;
            }

            $draftTerm->save();

            $this->petitionTermsUpdateAction->execute($petition);

            return $draftTerm;
        });
    }
}
