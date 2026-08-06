<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactType;
use App\Models\Casts\UuidCast;
use App\Models\Concerns\HasArchivedAt;
use App\Models\Concerns\HasDepartment;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use App\Models\Contracts\DepartmentAwareInterface;
use App\QueryBuilders\ContactQueryBuilder;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Override;
use Ramsey\Uuid\UuidInterface;
use Webmozart\Assert\Assert;

use function array_filter;
use function implode;

/**
 * @property ?string $initials
 * @property UuidInterface $department_id
 * @property ?string $last_name
 * @property ?string $middle_name
 * @property ?string $display_name
 * @property ?string $organisation_name
 * @property ?string $email_address
 * @property ?string $secondary_email_address
 * @property ?string $phone_number
 * @property ?string $street
 * @property ?string $house_number
 * @property ?string $postal_code
 * @property ?string $city
 * @property ?string $notes
 * @property ContactType $type
 * @property ?string $visiting_address_street
 * @property ?string $visiting_address_house_number
 * @property ?string $visiting_address_postal_code
 * @property ?string $visiting_address_city
 * @property ?string $postal_address_street
 * @property ?string $postal_address_house_number
 * @property ?string $postal_address_postal_code
 * @property ?string $postal_address_city
 * @property ?string $email_address_2
 * @property ?string $email_address_3
 *
 * @property-read string $full_name
 * @property-read ContactPetition $pivot
 */

#[Table('contacts')]
#[UseEloquentBuilder(ContactQueryBuilder::class)]
#[UseFactory(ContactFactory::class)]
class Contact extends EloquentModel implements DepartmentAwareInterface
{
    use HasArchivedAt;
    use HasDepartment;
    /** @use HasFactory<ContactFactory> */
    use HasFactory;
    use HasId;
    use HasTimestamps;

    /**
     * @return array<string, string>
     */
    #[Override]
    public function casts(): array
    {
        return [
            'type' => ContactType::class,
            'department_id' => UuidCast::class,
        ];
    }

    /**
     * @return BelongsToMany<Petition, $this, ContactPetition>
     */
    public function petitions(): BelongsToMany
    {
        return $this->belongsToMany(
            Petition::class,
            'contact_petition',
            'contact_id',
            'petition_id',
        )->using(ContactPetition::class)->withPivot('role', 'reference', 'correspondence_preference');
    }

    /**
     * @return Attribute<string, string>
     */
    protected function displayName(): Attribute
    {
        return Attribute::make(function (mixed $_value, array $attributes): string {
            Assert::nullOrString($attributes['organisation_name']);

            return $attributes['organisation_name'] ?? $this->full_name;
        });
    }

    /**
     * @return Attribute<string, string>
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(static function (mixed $value, array $attributes): string {
            $initials = $attributes['initials'] ?? null;
            $middleName = $attributes['middle_name'] ?? null;
            $lastName = $attributes['last_name'] ?? null;

            Assert::nullOrString($initials);
            Assert::nullOrString($middleName);
            Assert::nullOrString($lastName);

            return implode(' ', array_filter([$initials, $middleName, $lastName]));
        });
    }
}
