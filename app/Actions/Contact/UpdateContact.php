<?php

declare(strict_types=1);

namespace App\Actions\Contact;

use App\Models\Contact;

class UpdateContact
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(Contact $contact, array $data): Contact
    {
        $contact->update($data);

        return $contact;
    }
}
