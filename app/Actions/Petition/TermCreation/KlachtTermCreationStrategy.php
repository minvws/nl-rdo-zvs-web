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

readonly class KlachtTermCreationStrategy implements TermCreationStrategyInterface
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

        $this->createFirstTerm($petition, $dateOfEntry, $user);
    }

    /**
     * @throws Throwable
     */
    private function createFirstTerm(
        Petition $petition,
        CalendarDate $dateOfEntry,
        User $user,
    ): void {
        $firstTermSettings = DepartmentTermTypeSetting::whereDepartmentAndType($petition->department, TermType::FIRST)->get();

        $durationSetting = $firstTermSettings->firstWhere('field', 'duration_in_days');
        $durationInDays = (int) $durationSetting?->default_value;

        $startDate = $dateOfEntry->addDays(1);

        $this->petitionTermCreateAction->execute($petition, TermType::FIRST, $user, [
            'start_date' => $startDate->format('Y-m-d'),
            'duration_in_days' => (string) $durationInDays,
            'penalty_amount_in_euros' => '0',
        ]);
    }
}
