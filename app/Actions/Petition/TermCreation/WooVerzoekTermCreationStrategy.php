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
use Illuminate\Container\Attributes\Config;
use Throwable;
use Webmozart\Assert\Assert;

readonly class WooVerzoekTermCreationStrategy implements TermCreationStrategyInterface
{
    public function __construct(
        private PetitionTermCreateAction $petitionTermCreateAction,
        #[Config('petition_term.default_first_term_duration_in_days')]
        private int $defaultFirstTermDurationInDays,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @throws Throwable
     */
    public function createTerms(Petition $petition, array $attributes, User $user): void
    {
        $firstTermSettings = DepartmentTermTypeSetting::whereDepartmentAndType($petition->department, TermType::FIRST)->get();

        $dateOfEntry = $attributes['date_of_entry'];
        Assert::string($dateOfEntry);

        $durationInDaysSetting = $firstTermSettings->firstWhere('field', 'duration_in_days');
        $durationInDays = $durationInDaysSetting?->active
            ? (int) $durationInDaysSetting->default_value
            : $this->defaultFirstTermDurationInDays;

        $penaltyAmountSetting = $firstTermSettings->firstWhere('field', 'penalty_amount_in_euros');
        $penaltyAmountInEuros = (int) $penaltyAmountSetting?->default_value;

        $startDate = CalendarDate::createFromFormat('Y-m-d', $dateOfEntry)
            ->addDays(1)
            ->format('Y-m-d');

        $termAttributes = [
            'start_date' => $startDate,
            'duration_in_days' => (string) $durationInDays,
            'penalty_amount_in_euros' => $penaltyAmountInEuros,
        ];

        $this->petitionTermCreateAction->execute($petition, TermType::FIRST, $user, $termAttributes);
    }
}
