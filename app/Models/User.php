<?php

declare(strict_types=1);

namespace App\Models;

use App\Collections\UserCollection;
use App\Models\Casts\DatetimeWithTimezoneCast;
use App\Models\Casts\UuidCast;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use App\QueryBuilders\UserQueryBuilder;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use MinVWS\AuditLogger\Contracts\LoggableUser;
use Override;

/**
 * @method static UserQueryBuilder|static query()
 * @property string $name
 * @property string $email
 * @property bool $active
 * @property ?CarbonImmutable $email_verified_at
 * @property ?CarbonImmutable $otp_verified_at
 * @property ?string $otp_secret
 * @property ?string $password
 * @property ?string $remember_token
 * @property ?string $last_visited_department_id
 *
 * @property-read Collection<array-key, Department> $departments
 * @property-read Collection<int, PetitionAssignment> $petitionAssignments
 * @property-read Collection<int, ProcessingStepAssignment> $processingStepAssignments
 * @property-read Collection<int, UserDepartmentFilter> $departmentFilters
 * @property-read ?Department $lastVisitedDepartment
 */
#[CollectedBy(UserCollection::class)]
#[Hidden(['password', 'remember_token', 'otp_secret'])]
#[Table('users')]
#[UseEloquentBuilder(UserQueryBuilder::class)]
#[UseFactory(UserFactory::class)]
class User extends Authenticatable implements LoggableUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use HasId;
    use HasTimestamps;
    use Notifiable;

    /**
     * @return BelongsToMany<Department, $this, DepartmentUser>
     */
    public function departments(): BelongsToMany
    {
        return $this
            ->belongsToMany(Department::class, 'department_user', 'user_id', 'department_id')
            ->withPivot('role')
            ->using(DepartmentUser::class);
    }

    public function hasDepartments(): bool
    {
        return $this->departments()->exists();
    }

    /**
     * @return HasMany<PetitionAssignment, $this>
     */
    public function petitionAssignments(): HasMany
    {
        return $this->hasMany(PetitionAssignment::class);
    }

    /**
     * @return HasMany<ProcessingStepAssignment, $this>
     */
    public function processingStepAssignments(): HasMany
    {
        return $this->hasMany(ProcessingStepAssignment::class);
    }

    /**
     * @return HasMany<ProcessingStep, $this>
     */
    public function assignedProcessingSteps(): HasMany
    {
        return $this->hasMany(ProcessingStep::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function lastVisitedDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'last_visited_department_id');
    }

    public function getAuditId(): string
    {
        return $this->id->toString();
    }

    // @codeCoverageIgnoreStart
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array{}
     */
    public function getRoles(): array
    {
        return [];
    }

    public function getEmail(): string
    {
        return $this->email;
    }
    // @codeCoverageIgnoreEnd

    /**
     * @return HasMany<UserDepartmentFilter, $this>
     */
    public function departmentFilters(): HasMany
    {
        return $this->hasMany(UserDepartmentFilter::class, 'user_id');
    }

    /**
     * @return HasMany<UserGlobalRole, $this>
     */
    public function globalRoles(): HasMany
    {
        return $this->hasMany(UserGlobalRole::class, 'user_id', 'id');
    }

    #[Override]
    protected static function booted(): void
    {
        static::addGlobalScope('order_by_name', static function (Builder $builder): void {
            $builder->orderBy('name');
        });
    }

    /**
     * @return array<string, class-string<UuidCast>|class-string<DatetimeWithTimezoneCast>|string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'last_visited_department_id' => UuidCast::class,
            'email_verified_at' => DatetimeWithTimezoneCast::class,
            'otp_verified_at' => DatetimeWithTimezoneCast::class,
        ];
    }
}
