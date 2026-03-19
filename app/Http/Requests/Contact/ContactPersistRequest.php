<?php

declare(strict_types=1);

namespace App\Http\Requests\Contact;

use App\Enums\ContactType;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;
use Override;
use Webmozart\Assert\Assert;

use function str_replace;
use function strtoupper;

class ContactPersistRequest extends FormRequest
{
    private const string MAX_LENGTH_SMALL = 'max:20';
    private const string DUTCH_POSTAL_CODE_REGEX = '/^(?:[1-9]\d{3} ?(?:[A-RT-Z][A-Z]|S[BCE-RT-Z]))$/i';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'initials' => ['string', 'nullable', self::MAX_LENGTH_SMALL],
            'last_name' => ['string', 'max:255', 'nullable', 'required_without:organisation_name'],
            'middle_name' => ['string', 'max:255', 'nullable'],
            'organisation_name' => ['string', 'max:255', 'nullable', 'required_without:last_name'],
            'email_address' => ['string', 'max:255', 'nullable', 'email'],
            'secondary_email_address' => ['string', 'max:255', 'nullable', 'email'],
            'phone_number' => ['string', 'nullable', self::MAX_LENGTH_SMALL],
            'street' => ['string', 'nullable', 'max:255'],
            'house_number' => ['string', 'nullable', self::MAX_LENGTH_SMALL],
            'postal_code' => ['string', 'nullable', self::MAX_LENGTH_SMALL, 'regex:' . self::DUTCH_POSTAL_CODE_REGEX],
            'city' => ['string', 'nullable', 'max:255'],

            // Visiting address fields
            'visiting_address_street' => ['string', 'nullable', 'max:255'],
            'visiting_address_house_number' => ['string', 'nullable', self::MAX_LENGTH_SMALL],
            'visiting_address_postal_code' => ['string', 'nullable', self::MAX_LENGTH_SMALL, 'regex:' . self::DUTCH_POSTAL_CODE_REGEX],
            'visiting_address_city' => ['string', 'nullable', 'max:255'],

            // Postal address fields
            'postal_address_street' => ['string', 'nullable', 'max:255'],
            'postal_address_house_number' => ['string', 'nullable', self::MAX_LENGTH_SMALL],
            'postal_address_postal_code' => ['string', 'nullable', self::MAX_LENGTH_SMALL,'regex:' . self::DUTCH_POSTAL_CODE_REGEX],
            'postal_address_city' => ['string', 'nullable', 'max:255'],

            // Additional email addresses
            'email_address_2' => ['string', 'max:255', 'nullable', 'email'],
            'email_address_3' => ['string', 'max:255', 'nullable', 'email'],

            'notes' => ['string', 'nullable'],
            'type' => [Rule::enum(ContactType::class)],
        ];
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        //@codeCoverageIgnoreStart
        Assert::nullOrString($this->postal_code);
        if ($this->postal_code) {
            $this->merge([
                'postal_code' => strtoupper(str_replace(' ', '', $this->postal_code)),
            ]);
        }

        Assert::nullOrString($this->postal_address_postal_code);
        if ($this->postal_address_postal_code) {
            $this->merge([
                'postal_address_postal_code' => strtoupper(str_replace(' ', '', $this->postal_address_postal_code)),
            ]);
        }

        Assert::nullOrString($this->visiting_address_postal_code);
        if (!$this->visiting_address_postal_code) {
            return;
        }

        $this->merge([
            'visiting_address_postal_code' => strtoupper(str_replace(' ', '', $this->visiting_address_postal_code)),
        ]);
        //@codeCoverageIgnoreEnd
    }
}
