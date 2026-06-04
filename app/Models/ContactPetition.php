<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactRole;
use App\Enums\CorrespondencePreference;
use App\Models\Casts\UuidCast;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Override;
use Ramsey\Uuid\UuidInterface;

/**
 * @property int $id
 * @property UuidInterface $contact_id
 * @property UuidInterface $petition_id
 * @property ?CorrespondencePreference $correspondence_preference
 */
#[Table('contact_petition', key: 'id', timestamps: false)]
class ContactPetition extends Pivot
{
    /** @var bool The contact_petition table has a bigserial primary key */
    public $incrementing = true;

    #[Override]
    protected function casts(): array
    {
        return [
            'contact_id' => UuidCast::class,
            'petition_id' => UuidCast::class,
            'role' => ContactRole::class,
            'correspondence_preference' => CorrespondencePreference::class,
        ];
    }
}
