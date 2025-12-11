<?php

declare(strict_types=1);

namespace App\Actions\Petition;

use App\Collections\PetitionCustomDateCollection;
use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Throwable;

readonly class PetitionCustomDatesUpdateAction
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
    public function execute(
        Petition $petition,
        PetitionCustomDateCollection $customDates,
        User $user,
        array $attributes,
    ): void {
        $this->databaseManager->transaction(static function () use ($petition, $customDates, $user, $attributes): void {
            // Clear existing custom dates for this petition
            $petition->customDates()->delete();

            // Insert new custom dates
            foreach ($customDates as $customDate) {
                if ($customDate->date !== null) {
                    $petition->customDates()->create([
                        'date_label' => $customDate->date_label,
                        'date' => $customDate->date,
                    ]);
                }
            }

            $petition->timelineItems()->create([
                'user_id' => $user->id,
                'type' => TimelineType::PETITION_CUSTOM_DATES_CHANGED,
                'data' => new ArrayObject($attributes),
            ]);
        });
    }
}
