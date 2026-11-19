<?php

declare(strict_types=1);

namespace App\Actions\Petition\PetitionEventCreation;

use App\Actions\Petition\Contracts\PetitionEventsCreationStrategyInterface;
use App\Actions\PetitionEvent\PetitionEventsPersistenceAction;
use App\Enums\PetitionEventType;
use App\Models\Petition;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use Carbon\CarbonImmutable;
use Throwable;
use Webmozart\Assert\Assert;

readonly class CivieleProcedurePetitionEventsCreationStrategy implements PetitionEventsCreationStrategyInterface
{
    public function __construct(
        private PetitionEventsPersistenceAction $petitionEventsPersistenceAction,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @throws Throwable
     */
    public function create(Petition $petition, array $attributes, User $user): void
    {
        $dateOfEntry = $attributes['date_of_entry'];
        Assert::string($dateOfEntry);
        $dateOfEntry = CalendarDate::createFromFormat('Y-m-d', $dateOfEntry);

        $this->petitionEventsPersistenceAction->execute($petition, [
            [
                'date' => $dateOfEntry->toDateString(),
                'type' => PetitionEventType::RECEIPT_OF_CIVIL_PROCEEDING->value,
                'created_at' => CarbonImmutable::now(),
            ],
        ], $user);
    }
}
