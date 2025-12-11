<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContactType;
use App\Models\Contact;
use App\Models\Department;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /** @var class-string<Contact> $model */
    protected $model = Contact::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'department_id' => Department::factory(),
            'initials' => $this->faker->regexify('[A-Z]{0,3}'),
            'last_name' => $this->faker->lastName(),
            'organisation_name' => $this->faker->optional()->company(),
            'email_address' => $this->faker->optional()->safeEmail(),
            'email_address_2' => $this->faker->optional()->safeEmail(),
            'email_address_3' => $this->faker->optional()->safeEmail(),
            'phone_number' => $this->faker->optional()->phoneNumber(),
            'street' => $this->faker->optional()->streetName(),
            'house_number' => $this->faker->optional()->buildingNumber(),
            'postal_code' => $this->faker->optional()->postcode(),
            'city' => $this->faker->optional()->city(),
            'visiting_address_street' => $this->faker->optional()->streetName(),
            'visiting_address_house_number' => $this->faker->optional()->buildingNumber(),
            'visiting_address_postal_code' => $this->faker->optional()->postcode(),
            'visiting_address_city' => $this->faker->optional()->city(),
            'postal_address_street' => $this->faker->optional()->streetName(),
            'postal_address_house_number' => $this->faker->optional()->buildingNumber(),
            'postal_address_postal_code' => $this->faker->optional()->postcode(),
            'postal_address_city' => $this->faker->optional()->city(),
            'notes' => $this->faker->optional()->realText(100),
            'type' => $this->faker->randomElement(ContactType::cases()),
            'archived_at' => null,
        ];
    }
}
