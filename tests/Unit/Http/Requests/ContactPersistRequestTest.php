<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\Contact\ContactPersistRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(ContactPersistRequest::class)]
class ContactPersistRequestTest extends TestCase
{
    #[Test]
    #[DataProvider('validatorData')]
    public function testContactPersistRequestWithOnlyLastNameOrOrganisationName(?string $lastName, ?string $organisationName): void
    {
        $contactPersistRequest = new ContactPersistRequest();

        $validator = Validator::make([
            'last_name' => $lastName,
            'organisation_name' => $organisationName,
        ], $contactPersistRequest->rules());

        $this->assertTrue($validator->passes());
        $this->assertNotContains('last_name', $validator->errors()->keys());
        $this->assertNotContains('organisation_name', $validator->errors()->keys());
    }

    public function testContactStoreRequestDtoWithNoLastNameOrOrganisationThrowsError(): void
    {
        $contactPersistRequest = new ContactPersistRequest();

        $validator = Validator::make([
            'last_name' => null,
            'organisation_name' => null,
        ], $contactPersistRequest->rules());

        $this->assertFalse($validator->passes());
        $this->assertContains('last_name', $validator->errors()->keys());
        $this->assertContains('organisation_name', $validator->errors()->keys());
    }

    public function testContactPersistRequestWithNotes(): void
    {
        $contactPersistRequest = new ContactPersistRequest();
        $validator = Validator::make([
            'last_name' => $this->faker->lastName(),
            'notes' => $this->faker->paragraph(),
        ], $contactPersistRequest->rules());
        $this->assertTrue($validator->passes());
        $this->assertNotContains('notes', $validator->errors()->keys());
    }

    /**
     * @return array<array{string|null, string|null}>
     */
    public static function validatorData(): array
    {
        return [
            [ 'jansen', null ],
            [ null, 'test BV' ],
        ];
    }

    #[Test]
    #[DataProvider('postalCodeData')]
    public function testContactPersistRequestWithPostalCodes(string $field, string $code, bool $expectation): void
    {
        $contactPersistRequest = new ContactPersistRequest();
        $validator = Validator::make([
            $field => $code,
        ], $contactPersistRequest->rules());
        if ($expectation) {
            $this->assertNotContains($field, $validator->errors()->keys());
        }
        if (!$expectation) {
            $this->assertContains($field, $validator->errors()->keys());
        }
    }

    /**
     * @return array<array{string|null, string|null}>
     */
    public static function postalCodeData(): array
    {
        return [
            [ 'postal_code', '1234AB', true ],
            [ 'postal_address_postal_code', '5678CD', true ],
            [ 'visiting_address_postal_code', '9012EF', true ],
            [ 'postal_code', 'invalid postal code', false ],
            [ 'postal_address_postal_code', '1111SS', false ], // SS is not a valid postal code in NL, see https://nl.wikipedia.org/wiki/Postcodes_in_Nederland
            [ 'visiting_address_postal_code', 'invalid postal code', false ],
        ];
    }
}
