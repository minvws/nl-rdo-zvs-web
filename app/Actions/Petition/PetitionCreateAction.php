<?php

declare(strict_types=1);

namespace App\Actions\Petition;

use App\Actions\Petition\PetitionEventCreation\PetitionEventCreationStrategyResolver;
use App\Actions\Petition\TermCreation\TermCreationStrategyResolver;
use App\Enums\TimelineType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionCategory;
use App\Models\PetitionStatus;
use App\Models\PetitionStatusHistory;
use App\Models\PetitionType;
use App\Models\User;
use App\Services\Petition\PetitionNumberGeneratorInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Throwable;

readonly class PetitionCreateAction
{
    public function __construct(
        private PetitionNumberGeneratorInterface $petitionNumberGenerator,
        private DatabaseManager $databaseManager,
        private PetitionEventCreationStrategyResolver $petitionEventCreationStrategyResolver,
        private TermCreationStrategyResolver $termCreationStrategyResolver,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @throws Throwable
     */
    public function execute(Department $department, User $user, PetitionType $petitionType, array $attributes): Petition
    {
        $attributes = $this->prepareNameAttribute($attributes);

        return $this->databaseManager->transaction(
            function () use ($department, $user, $petitionType, $attributes): Petition {
                $petitionNumber = isset($attributes['number']) && $attributes['number'] !== ''
                    ? $attributes['number']
                    : $this->petitionNumberGenerator->generate($department);

                $fixedAttributes = [
                    'department_id' => $department->id,
                    'number' => $petitionNumber,
                    'petition_type_id' => $petitionType->id,
                    'petition_status_id' => $this->getDefaultStatus($petitionType)->id,
                ];
                $petition = Petition::query()->create([...$attributes, ...$fixedAttributes]);

                if ($petition->isTermEngineConverted()) {
                    $this->createPetitionEvents($petition, $attributes, $user);
                } else {
                    $this->createTerms($petition, $attributes, $user);
                }

                $petition->timelineItems()->create([
                    'user_id' => $user->id,
                    'type' => TimelineType::TIMELINEABLE_CREATED,
                ]);

                $this->createPetitionStatusHistory($petition, $user);

                return $petition;
            },
        );
    }

    private function getDefaultStatus(PetitionType $petitionType): PetitionStatus
    {
        return PetitionStatus::query()->where('petition_type_id', $petitionType->id)
            ->orderBy('order', 'asc')
            ->select('id')
            ->firstOrFail();
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @throws Throwable
     */
    private function createPetitionEvents(Petition $petition, array $attributes, User $user): void
    {
        $strategy = $this->petitionEventCreationStrategyResolver->resolve($petition->petitionType->type);
        $strategy->create($petition, $attributes, $user);
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @throws Throwable
     */
    private function createTerms(Petition $petition, array $attributes, User $user): void
    {
        $strategy = $this->termCreationStrategyResolver->resolve($petition->petitionType->type);
        $strategy->createTerms($petition, $attributes, $user);
    }

    private function createPetitionStatusHistory(Petition $petition, User $user): void
    {
        $date = $petition->date_of_entry->toDateString();

        PetitionStatusHistory::query()->create([
            'petition_id' => $petition->id,
            'petition_status_id' => $petition->petition_status_id,
            'date' => $date,
        ]);

        $petition->timelineItems()->create([
            'user_id' => $user->id,
            'type' => TimelineType::STATUS_OCCURRENCE,
            'data' => new ArrayObject([
                'previous_status' => null,
                'current_status' => $petition->petitionStatus->status,
                'date' => $date,
            ]),
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>
     */
    private function prepareNameAttribute(array $attributes): array
    {
        if ((!isset($attributes['name']) || $attributes['name'] === '') && isset($attributes['petition_category_id'])) {
            $category = PetitionCategory::query()->find($attributes['petition_category_id']);
            $attributes['name'] = $category->name ?? '';
        }

        return $attributes;
    }
}
