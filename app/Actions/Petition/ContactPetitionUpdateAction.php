<?php

declare(strict_types=1);

namespace App\Actions\Petition;

use App\Enums\TimelineType;
use App\Models\Contact;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;

readonly class ContactPetitionUpdateAction
{
    public function __construct(private DatabaseManager $databaseManager)
    {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function execute(Petition $petition, Contact $contact, User $user, array $attributes): void
    {
        $this->databaseManager->transaction(static function () use ($petition, $contact, $user, $attributes): void {
            $pivotData = [
                'reference' => $attributes['reference'] ?? null,
                'correspondence_preference' => $attributes['correspondence_preference'] ?? null,
            ];

            $petition->contacts()->updateExistingPivot($contact->id, $pivotData);

            $petition->timelineItems()->create([
                'type' => TimelineType::CONTACT_PIVOT_UPDATED,
                'user_id' => $user->id,
                'data' => new ArrayObject([
                    'contact_id' => $contact->id->toString(),
                    'reference' => $pivotData['reference'],
                    'correspondence_preference' => $pivotData['correspondence_preference'],
                ]),
            ]);
        });
    }
}
