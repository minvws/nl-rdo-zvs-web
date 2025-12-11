<?php

declare(strict_types=1);

namespace App\Actions\Terms;

use App\Actions\Petition\PetitionTermsUpdateAction;
use App\Enums\TermType;
use App\Enums\TimelineType;
use App\Models\Department;
use App\Models\DepartmentTermTypeSetting;
use App\Models\PetitionTerm;
use App\Models\User;
use App\Services\Terms\TermDateCalculator;
use App\ValueObjects\CalendarDate;
use Illuminate\Database\DatabaseManager;
use Throwable;
use Webmozart\Assert\Assert;

use function array_key_exists;

readonly class PetitionTermUpdateAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
        private PetitionTermsUpdateAction $petitionTermsUpdateAction,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @throws Throwable
     */
    public function execute(Department $department, PetitionTerm $petitionTerm, User $user, array $attributes): void
    {
        if ($petitionTerm->type === TermType::PENALTY) {
            $departmentTermTypeSettings = DepartmentTermTypeSetting::whereDepartmentAndType($department, $petitionTerm->type)->get();
            $endDateSetting = $departmentTermTypeSettings->firstWhere('field', 'end_date');

            if ($endDateSetting?->active === true) {
                $requestedEndDate = $attributes['end_date'];
                Assert::string($requestedEndDate);

                $requestedEndDate = CalendarDate::createFromFormat('Y-m-d', $requestedEndDate);
                $requestedDuration = TermDateCalculator::calculateDuration($petitionTerm->start_date, $requestedEndDate);

                $attributes['duration_in_days'] = $requestedDuration;
            }
        }

        $this->databaseManager->transaction(function () use ($petitionTerm, $user, $attributes): void {
            if (!array_key_exists('duration_in_days', $attributes)) {
                Assert::string($attributes['end_date']);
                $petitionTerm->duration_in_days = TermDateCalculator::calculateDuration(
                    $petitionTerm->start_date,
                    CalendarDate::create($attributes['end_date']),
                );
            }
            $petitionTerm->update($attributes);

            $petitionTerm->petition->timelineItems()->create([
                'user_id' => $user->id,
                'type' => TimelineType::TERM_UPDATED,
                'data' => [
                    'term_type' => $petitionTerm->type->value,
                ],
            ]);

            $this->petitionTermsUpdateAction->execute($petitionTerm->petition);
        });
    }
}
