<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects;

use App\Models\Contact;
use App\ValueObjects\Address;
use Tests\TestCase;

class AddressTest extends TestCase
{
    public function testFromContact(): void
    {
        $contact = Contact::factory()->make([
            'department_id' => $this->faker->uuid(),
            'initials' => $this->faker->optional()->word(),
            'last_name' => $this->faker->optional()->word(),
            'organisation_name' => $this->faker->optional()->word(),
            'street' => $this->faker->optional()->word(),
            'house_number' => $this->faker->optional()->word(),
            'postal_code' => $this->faker->optional()->word(),
            'city' => $this->faker->optional()->word(),
        ]);

        $address = Address::fromContact($contact);

        $this->assertEquals($address->initials, $contact->initials);
        $this->assertEquals($address->lastName, $contact->last_name);
        $this->assertEquals($address->organisationName, $contact->organisation_name);
        $this->assertEquals($address->street, $contact->street);
        $this->assertEquals($address->houseNumber, $contact->house_number);
        $this->assertEquals($address->postalCode, $contact->postal_code);
        $this->assertEquals($address->city, $contact->city);
    }
}
