<?php

declare(strict_types=1);

namespace App\Actions\Contact;

use App\Models\Contact;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Throwable;

readonly class ContactArchiveAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function execute(Contact $contact, User $user): void
    {
        if ($contact->archived_at !== null) {
            return;
        }

        $this->databaseManager->transaction(static function () use ($contact): void {
            $contact->update(['archived_at' => CarbonImmutable::now()]);
        });
    }
}
