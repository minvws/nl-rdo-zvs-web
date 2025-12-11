<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Models\Contact;

readonly class Address
{
    public function __construct(
        public ?string $initials,
        public ?string $lastName,
        public ?string $organisationName,
        public ?string $street,
        public ?string $houseNumber,
        public ?string $postalCode,
        public ?string $city,
    ) {
    }

    public static function fromContact(Contact $object): self
    {
        return new self(
            $object->initials,
            $object->last_name,
            $object->organisation_name,
            $object->street,
            $object->house_number,
            $object->postal_code,
            $object->city,
        );
    }
}
