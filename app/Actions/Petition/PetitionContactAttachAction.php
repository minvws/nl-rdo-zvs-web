<?php

declare(strict_types=1);

namespace App\Actions\Petition;

use App\Enums\ContactRole;
use App\Enums\TimelineType;
use App\Models\Contact;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;

readonly class PetitionContactAttachAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    public function execute(ContactRole $role, Petition $petition, Contact $contact, User $user): void
    {
        $this->databaseManager->transaction(static function () use ($petition, $role, $contact, $user): void {
            $petition->contacts()->attach($contact, ['role' => $role->value]);

            $petition
                ->timelineItems()
                ->create([
                    'type' => TimelineType::CONTACT_ATTACHED,
                    'user_id' => $user->id,
                    'data' => new ArrayObject([
                        'contact_id' => $contact->id->toString(),
                        'role' => $role->value,
                    ]),
                ]);
        });
    }
}
