<?php

declare(strict_types=1);

namespace App\Actions\Petition\TermCreation;

use App\Actions\Petition\Contracts\TermCreationStrategyInterface;
use App\Actions\Terms\PetitionTermCreateAction;
use App\Enums\TermType;
use App\Models\DepartmentTermTypeSetting;
use App\Models\Petition;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use Throwable;
use Webmozart\Assert\Assert;

readonly class BezwaarTermCreationStrategy implements TermCreationStrategyInterface
{
    public function __construct(
        private PetitionTermCreateAction $petitionTermCreateAction,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @throws Throwable
     */
    public function createTerms(Petition $petition, array $attributes, User $user): void
    {
        $dateOfEntry = $attributes['date_of_entry'];
        Assert::string($dateOfEntry);
        $dateOfEntry = CalendarDate::createFromFormat('Y-m-d', $dateOfEntry);

        $dateAppealedDecision = $attributes['date_appealed_decision'];
        Assert::string($dateAppealedDecision);
        $dateAppealedDecision = CalendarDate::createFromFormat('Y-m-d', $dateAppealedDecision);

        $this->createObjectionPeriodTerm($petition, $dateAppealedDecision, $user);
        $this->createFirstTerm($petition, $dateAppealedDecision, $dateOfEntry, $user);
    }

    /**
     * @throws Throwable
     */
    private function createObjectionPeriodTerm(
        Petition $petition,
        CalendarDate $dateAppealedDecision,
        User $user,
    ): void {
        $objectionPeriodTermSettings = DepartmentTermTypeSetting::whereDepartmentAndType(
            $petition->department,
            TermType::OBJECTION_PERIOD,
        )->get();

        $durationSetting = $objectionPeriodTermSettings->firstWhere('field', 'duration_in_days');
        $objectionPeriodTermDurationInDays = (int) $durationSetting?->default_value;

        $this->petitionTermCreateAction->execute($petition, TermType::OBJECTION_PERIOD, $user, [
            'start_date' => $dateAppealedDecision->addDays(1)->format('Y-m-d'),
            'duration_in_days' => (string) $objectionPeriodTermDurationInDays,
            'penalty_amount_in_euros' => '0',
        ]);
    }

    /**
     * @throws Throwable
     */
    private function createFirstTerm(
        Petition $petition,
        CalendarDate $dateAppealedDecision,
        CalendarDate $dateOfEntry,
        User $user,
    ): void {
        $firstTermSettings = DepartmentTermTypeSetting::whereDepartmentAndType($petition->department, TermType::FIRST)->get();

        $objectionPeriodTermSettings = DepartmentTermTypeSetting::whereDepartmentAndType(
            $petition->department,
            TermType::OBJECTION_PERIOD,
        )->get();

        $durationSetting = $objectionPeriodTermSettings->firstWhere('field', 'duration_in_days');
        $objectionPeriodTermDurationInDays = (int) $durationSetting?->default_value;

        $objectionPeriodTermStartDate = $dateAppealedDecision->addDays(1);
        $objectionPeriodEndDate = $objectionPeriodTermStartDate->addDays($objectionPeriodTermDurationInDays);

        $firstTermStartDate = $objectionPeriodEndDate > $dateOfEntry
            ? $objectionPeriodEndDate
            : $dateOfEntry->addDay();

        $firstTermDurationSetting = $firstTermSettings->firstWhere('field', 'duration_in_days');
        $firstTermDurationInDays = (int) $firstTermDurationSetting?->default_value;

        $this->petitionTermCreateAction->execute($petition, TermType::FIRST, $user, [
            'start_date' => $firstTermStartDate->format('Y-m-d'),
            'duration_in_days' => (string) $firstTermDurationInDays,
            'penalty_amount_in_euros' => '0',
        ]);
    }
}
