<?php

declare(strict_types=1);

namespace App\Actions\Petition;

use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Throwable;

readonly class PetitionFinancialInterestUpdateAction
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
        $this->databaseManager->transaction(
            static function () use ($petition, $user, $attributes): void {
                $petition->fill($attributes);

                if (!$petition->isDirty('amount_in_dispute')) {
                    return;
                }

                $petition->save();

                $petition->timelineItems()->create([
                    'user_id' => $user->id,
                    'type' => TimelineType::FINANCIAL_INTEREST_UPDATED,
                    'data' => new ArrayObject([
                        'amount_in_dispute' => $petition->amount_in_dispute,
                    ]),
                ]);
            },
        );
    }
}
