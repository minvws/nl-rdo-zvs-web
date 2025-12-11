<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactRole;
use App\Enums\CorrespondencePreference;
use App\Models\Casts\UuidCast;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Ramsey\Uuid\UuidInterface;

/**
 * @property int $id
 * @property UuidInterface $contact_id
 * @property UuidInterface $petition_id
 * @property ContactRole $role
 * @property ?string $reference
 * @property ?CorrespondencePreference $correspondence_preference
 */
class ContactPetition extends Pivot
{
    public $incrementing = true;
    protected $table = 'contact_petition';

    protected $primaryKey = 'id';

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
