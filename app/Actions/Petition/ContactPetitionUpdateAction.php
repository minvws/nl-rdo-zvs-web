<?php

declare(strict_types=1);

namespace App\Actions\Petition;

use App\Enums\TimelineType;
use App\Models\Contact;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Webmozart\Assert\Assert;

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
        $this->databaseManager->transaction(function () use ($petition, $contact, $user, $attributes): void {
            $pivotData = [
                'reference' => $attributes['reference'] ?? null,
                'correspondence_preference' => $attributes['correspondence_preference'] ?? null,
                'date_first_phone_contact' => $attributes['date_first_phone_contact'] ?? null,
            ];

            $previousDateFirstPhoneContact = $this->currentDateFirstPhoneContact($petition, $contact);

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

            $this->registerFirstPhoneContact(
                $petition,
                $contact,
                $user,
                $previousDateFirstPhoneContact,
                $pivotData['date_first_phone_contact'],
            );
        });
    }

    private function currentDateFirstPhoneContact(Petition $petition, Contact $contact): ?string
    {
        $attached = $petition->contacts()->where('contacts.id', $contact->id)->first();

        return $attached?->pivot->date_first_phone_contact?->format('Y-m-d');
    }

    /**
     * The "eerste telefonisch contact" moment gets its own entry on the activity timeline, so it
     * stays readable next to the generic "contact details changed" entry.
     */
    private function registerFirstPhoneContact(
        Petition $petition,
        Contact $contact,
        User $user,
        ?string $previousDate,
        mixed $newDate,
    ): void {
        if ($newDate === null || $newDate === $previousDate) {
            return;
        }

        Assert::string($newDate);

        $petition->timelineItems()->create([
            'type' => TimelineType::CONTACT_FIRST_PHONE_CONTACT,
            'user_id' => $user->id,
            'data' => new ArrayObject([
                'contact_id' => $contact->id->toString(),
                'date' => $newDate,
            ]),
        ]);
    }
}
