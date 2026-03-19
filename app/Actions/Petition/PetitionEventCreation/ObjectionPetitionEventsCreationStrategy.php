<?php

declare(strict_types=1);

namespace App\Actions\Petition\PetitionEventCreation;

use App\Actions\Petition\Contracts\PetitionEventsCreationStrategyInterface;
use App\Actions\PetitionEvent\PetitionEventsPersistenceAction;
use App\Config\DepartmentConfigurationService;
use App\Enums\PetitionEventType;
use App\Models\Petition;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use Carbon\CarbonImmutable;
use Throwable;
use Webmozart\Assert\Assert;

readonly class ObjectionPetitionEventsCreationStrategy implements PetitionEventsCreationStrategyInterface
{
    public function __construct(
        private PetitionEventsPersistenceAction $petitionEventsPersistenceAction,
        private DepartmentConfigurationService $departmentConfigurationService,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @throws Throwable
     */
    public function create(Petition $petition, array $attributes, User $user): void
    {
        $petitionEventDataArray = [];

        $dateOfEntry = $attributes['date_of_entry'];
        Assert::string($dateOfEntry);
        $dateOfEntry = CalendarDate::createFromFormat('Y-m-d', $dateOfEntry);

        $config = $this->departmentConfigurationService->getEventConfiguration(
            $petition->department,
            $petition->petitionType->type,
            PetitionEventType::RECEIPT_OF_OBJECTION,
        );

        $duration = $config['duration'];
        Assert::integerish($duration);
        $duration = (int) $duration;

        $petitionEventDataArray[] = [
            'date' => $dateOfEntry->toDateString(),
            'type' => PetitionEventType::RECEIPT_OF_OBJECTION->value,
            'duration' => $duration,
            'created_at' => CarbonImmutable::now(),
        ];

        $dateAppealedDecision = $attributes['date_appealed_decision'];
        Assert::string($dateAppealedDecision);
        $dateAppealedDecision = CalendarDate::createFromFormat('Y-m-d', $dateAppealedDecision);

        $config = $this->departmentConfigurationService->getEventConfiguration(
            $petition->department,
            $petition->petitionType->type,
            PetitionEventType::PRIMARY_DECISION,
        );

        $duration = $config['duration'];
        Assert::integerish($duration);
        $duration = (int) $duration;

        $petitionEventDataArray[] = [
            'date' => $dateAppealedDecision->toDateString(),
            'type' => PetitionEventType::PRIMARY_DECISION->value,
            'duration' => $duration,
            'created_at' => CarbonImmutable::now(),
        ];

        $this->petitionEventsPersistenceAction->execute($petition, $petitionEventDataArray, $user);
    }
}
