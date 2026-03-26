<?php

declare(strict_types=1);

namespace App\Models;

use App\Collections\CustomPetitionPropertyCollection;
use App\Collections\PetitionCollection;
use App\Collections\PetitionCustomDateCollection;
use App\Collections\PetitionDeliverableCollection;
use App\Collections\PetitionTermCollection;
use App\Collections\PetitionTypeCustomDateLabelCollection;
use App\Collections\PolicyDepartmentCollection;
use App\Enums\ContactRole;
use App\Models\Casts\CalendarDateCast;
use App\Models\Casts\UuidCast;
use App\Models\Concerns\HasArchivedAt;
use App\Models\Concerns\HasDepartment;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use App\Models\Contracts\DepartmentAwareInterface;
use App\Models\Contracts\TimelineableInterface;
use App\Policies\PetitionPolicy;
use App\QueryBuilders\PetitionQueryBuilder;
use App\ValueObjects\CalendarDate;
use Database\Factories\PetitionFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Kingmaker\Illuminate\Eloquent\Relations\BelongsToManySelf;
use Kingmaker\Illuminate\Eloquent\Relations\HasBelongsToManySelfRelation;
use Override;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface $department_id
 * @property CalendarDate $date_of_entry
 * @property ?CalendarDate $date_appealed_decision
 * @property ?CalendarDate $deadline_at
 * @property ?string $description
 * @property ?string $name
 * @property string $number
 * @property UuidInterface $petition_type_id
 * @property UuidInterface $petition_status_id
 * @property ?UuidInterface $assigned_to
 * @property ?string $message
 * @property ?CalendarDate $date_of_message
 * @property ?UuidInterface $petition_category_id
 * @property ?UuidInterface $applicant_id
 * @property ?UuidInterface $representative_id
 * @property ?UuidInterface $institution_id
 * @property ?CalendarDate $date_of_close
 * @property ?string $decision_reference
 * @property ?CalendarDate $decision_date
 * @property int $total_days_suspended
 * @property int $igs_penalty_today
 * @property int $bnt_penalty_today
 * @property int $igs_forfeited
 * @property int $bnt_forfeited
 * @property int $igs_penalty_maximum
 * @property int $bnt_penalty_maximum
 * @property int $legacy_term_penalty_today
 * @property int $legacy_term_forfeited
 * @property int $legacy_term_penalty_maximum
 *
 * @property-read int $daysPending
 * @property-read Collection<int, Contact> $applicant
 * @property-read Collection<int, Contact> $stakeholders
 * @property-read Collection<int, CustomCost> $customCosts
 * @property-read PetitionCustomDateCollection $customDates
 * @property-read Collection<int, PetitionExternalUrl> $externalUrls
 * @property-read Department $department
 * @property-read ?PetitionCategory $petitionCategory
 * @property-read PolicyDepartmentCollection $policyDepartments
 * @property-read PetitionDeliverableCollection $petitionDeliverables
 * @property-read PetitionStatus $petitionStatus
 * @property-read PetitionTermCollection $petitionTerms
 * @property-read ?PetitionDraftTerm $draftTerm
 * @property-read PetitionType $petitionType
 * @property-read Collection<int, Contact> $representative
 * @property-read ?User $assignedUser
 * @property-read PetitionTypeCustomDateLabelCollection $availableCustomDates
 * @property-read PetitionCollection $relatedPetitions
 * @property-read CustomPetitionPropertyCollection $customPetitionProperties
 * @property-read CustomPetitionPropertyCollection $availableCustomPetitionProperties
 * @property-read Collection<int, TimelineItem> $timelineItems
 * @property-read Collection<int, Contact> $institution
 * @property-read Collection<int, PetitionEvent> $petitionEvents
 * @property-read Collection<int, PetitionQuerysnapshot> $querysnapshots
 *
 * @SuppressWarnings(PHPMD)
 */
#[CollectedBy(PetitionCollection::class)]
#[UseEloquentBuilder(PetitionQueryBuilder::class)]
#[UsePolicy(PetitionPolicy::class)]
class Petition extends EloquentModel implements DepartmentAwareInterface, TimelineableInterface
{
    use HasDepartment;
    /** @use HasFactory<PetitionFactory> */
    use HasFactory;
    use HasId;
    use HasTimestamps;
    use HasArchivedAt;
    use HasBelongsToManySelfRelation;

    protected $table = 'petitions';

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to', 'id');
    }

    /**
     * @return BelongsTo<PetitionType, $this>
     */
    public function petitionType(): BelongsTo
    {
        return $this->belongsTo(PetitionType::class, 'petition_type_id', 'id');
    }

    /**
     * @return BelongsTo<PetitionCategory, $this>
     */
    public function petitionCategory(): BelongsTo
    {
        return $this->belongsTo(PetitionCategory::class, 'petition_category_id', 'id');
    }

    /**
     * @return HasMany<PetitionDeliverable, $this>
     */
    public function petitionDeliverables(): HasMany
    {
        return $this->hasMany(PetitionDeliverable::class);
    }

    /**
     * @return BelongsTo<PetitionStatus, $this>
     */
    public function petitionStatus(): BelongsTo
    {
        return $this->belongsTo(PetitionStatus::class, 'petition_status_id', 'id');
    }

    /**
     * @return BelongsToMany<PolicyDepartment, $this>
     */
    public function policyDepartments(): BelongsToMany
    {
        return $this->belongsToMany(PolicyDepartment::class);
    }

    /**
     * @return BelongsToMany<Contact, $this, ContactPetition>
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(
            Contact::class,
            'contact_petition',
            'petition_id',
            'contact_id',
        )->using(ContactPetition::class)->withPivot('role', 'reference', 'correspondence_preference');
    }

    /**
     * @return BelongsToMany<Contact, $this, ContactPetition>
     */
    public function applicant(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_petition', 'petition_id', 'contact_id')
            ->using(ContactPetition::class)
            ->wherePivot('role', ContactRole::APPLICANT)
            ->withPivot('role', 'reference', 'correspondence_preference');
    }

    /**
     * @return BelongsToMany<Contact, $this, ContactPetition>
     */
    public function representative(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_petition', 'petition_id', 'contact_id')
            ->using(ContactPetition::class)
            ->wherePivot('role', ContactRole::REPRESENTATIVE)
            ->withPivot('role', 'reference', 'correspondence_preference');
    }

    /**
     * @return BelongsToMany<Contact, $this, ContactPetition>
     */
    public function institution(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_petition', 'petition_id', 'contact_id')
            ->using(ContactPetition::class)
            ->wherePivot('role', ContactRole::INSTITUTION)
            ->withPivot('role', 'reference', 'correspondence_preference');
    }

    /**
     * @return BelongsToMany<Contact, $this, ContactPetition>
     */
    public function stakeholders(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_petition', 'petition_id', 'contact_id')
            ->using(ContactPetition::class)
            ->wherePivot('role', ContactRole::STAKEHOLDER)
            ->withPivot('role', 'reference', 'correspondence_preference');
    }

    /**
     * @return BelongsToMany<Decision, $this>
     */
    public function decisions(): BelongsToMany
    {
        return $this->belongsToMany(Decision::class);
    }

    /**
     * @return BelongsToMany<CustomPetitionProperty, $this>
     */
    public function customPetitionProperties(): BelongsToMany
    {
        return $this->belongsToMany(
            CustomPetitionProperty::class,
            'custom_petition_property_petition',
            'petition_id',
            'custom_petition_property_id',
        );
    }

    /**
     * @return HasMany<PetitionTypeCustomDateLabel, $this>
     */
    public function availableCustomDates(): HasMany
    {
        return $this->hasMany(PetitionTypeCustomDateLabel::class, 'petition_type_id', 'petition_type_id');
    }

    /**
     * @return HasMany<CustomPetitionProperty, $this>
     */
    public function availableCustomPetitionProperties(): HasMany
    {
        return $this->hasMany(CustomPetitionProperty::class, 'petition_type_id', 'petition_type_id')->orderBy('ordering');
    }

    /**
     * @return HasMany<PetitionTerm, $this>
     */
    public function petitionTerms(): HasMany
    {
        return $this->hasMany(PetitionTerm::class, 'petition_id')->oldest('start_date')->orderBy('type');
    }

    /**
     * @return HasOne<PetitionDraftTerm, $this>
     */
    public function draftTerm(): HasOne
    {
        return $this->hasOne(PetitionDraftTerm::class, 'petition_id');
    }

    /**
     * @return HasMany<CustomCost, $this>
     */
    public function customCosts(): HasMany
    {
        return $this->hasMany(CustomCost::class, 'petition_id');
    }

    /**
     * @return HasMany<PetitionCustomDate, $this>
     */

    /**
     * @return HasMany<PetitionCustomDate, $this>
     */
    public function customDates(): HasMany
    {
        return $this->hasMany(PetitionCustomDate::class, 'petition_id');
    }

    /**
     * @return HasMany<PetitionExternalUrl, $this>
     */
    public function externalUrls(): HasMany
    {
        return $this->hasMany(PetitionExternalUrl::class, 'petition_id');
    }

    public function relatedPetitions(): BelongsToManySelf
    {
        return $this->belongsToManySelf('petition_petition', 'petition_id', 'related_petition_id');
    }

    /**
     * @return MorphMany<TimelineItem, $this>
     */
    public function timelineItems(): MorphMany
    {
        return $this->morphMany(TimelineItem::class, 'timelineable')->latest()->chaperone();
    }

    /**
     * @return HasMany<PetitionStatusHistory, $this>
     */
    public function petitionStatusHistories(): HasMany
    {
        return $this->hasMany(PetitionStatusHistory::class, 'petition_id');
    }

    /**
     * @return HasMany<PetitionQuerysnapshot, $this>
     */
    public function querysnapshots(): HasMany
    {
        return $this->hasMany(PetitionQuerysnapshot::class, 'petition_id');
    }

    /**
     * @return HasMany<PetitionEvent, $this>
     */
    public function petitionEvents(): HasMany
    {
        return $this->hasMany(PetitionEvent::class, 'petition_id');
    }

    public function isTermEngineConverted(): bool
    {
        if ($this->petitionEvents()->exists()) {
            return true;
        }

        if ($this->petitionTerms()->exists()) {
            return false;
        }

        return (bool) Config::get('app.features.term_engine_v2', false);
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'petition_type_id' => UuidCast::class,
            'petition_status_id' => UuidCast::class,
            'petition_category_id' => UuidCast::class,
            'department_id' => UuidCast::class,
            'date_of_entry' => CalendarDateCast::class,
            'deadline_at' => CalendarDateCast::class,
            'assigned_to' => UuidCast::class,
            'date_of_message' => CalendarDateCast::class,
            'date_of_close' => CalendarDateCast::class,
            'date_appealed_decision' => CalendarDateCast::class,
            'decision_date' => CalendarDateCast::class,
        ];
    }

    /**
     * @return Attribute<?CalendarDate, $this>
     */
    protected function dateOfClose(): Attribute
    {
        return Attribute::make(get: $this->customDates->getMaxDateForDateOfClose(...));
    }

    /**
     * @return Attribute<int, $this>
     */
    protected function daysPending(): Attribute
    {
        return Attribute::make(get: function () {
            if ($this->date_of_close === null) {
                return $this->date_of_entry->diffInDays(CalendarDate::today());
            }

            return $this->date_of_entry->diffInDays($this->date_of_close);
        });
    }
}
