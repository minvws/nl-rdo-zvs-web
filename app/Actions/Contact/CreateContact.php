<?php

declare(strict_types=1);

namespace App\Actions\Contact;

use App\Models\Contact;

class CreateContact
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(array $data): Contact
    {
        return Contact::query()->create($data);
    }
}
