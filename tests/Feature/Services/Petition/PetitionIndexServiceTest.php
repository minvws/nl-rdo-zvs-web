<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Petition;

use App\Enums\PetitionEventType;
use App\Enums\SuspensionType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionEvent;
use App\Models\PetitionType;
use App\Services\Petition\PetitionIndexService;
use App\ValueObjects\CalendarDate;
use Tests\Feature\FeatureTestCase;

class PetitionIndexServiceTest extends FeatureTestCase
{
    public function testUsedPetitionParticularitiesAreSortedAndOnlyContainLabelsOccurringInTheDepartment(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $this->relatePetitionWithLabel($petition, 'Zulu');
        $this->relatePetitionWithLabel($petition, 'Alpha');
        PetitionEvent::factory()
            ->recycle($petition)
            ->withType(PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY)
            ->create();

        $this->assertSame(['Alpha', 'Ontvangst BNT', 'Zulu'], $this->usedParticularities($department));
    }

    public function testUsedPetitionParticularitiesIncludeLabelOfRelationCreatedFromTheRelatedPetition(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $relatedPetition = $this->createPetitionWithLabel('IB');
        $relatedPetition->relatedPetitions()->attach($petition);

        $this->assertSame(['IB'], $this->usedParticularities($department));
    }

    public function testUsedPetitionParticularitiesExcludeLabelsNotOccurringInTheDepartment(): void
    {
        $department = Department::factory()->create();
        Petition::factory()->recycle($department)->create();

        $otherPetition = $this->createPetitionWithLabel('Alpha');
        $this->relatePetitionWithLabel($otherPetition, 'Zulu');
        PetitionEvent::factory()
            ->recycle($otherPetition)
            ->withType(PetitionEventType::ADJOURNMENT)
            ->create();

        $this->assertSame([], $this->usedParticularities($department));
    }

    public function testUsedPetitionParticularitiesExcludeCalculatedLabelsThatAreNoLongerActive(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        PetitionEvent::factory()
            ->recycle($petition)
            ->withType(PetitionEventType::LETTER_OF_SUSPENSION_SENT)
            ->create([
                'date' => CalendarDate::today()->subDays(10),
                'duration' => 30,
                'suspension_type' => SuspensionType::SUSPENSION,
            ]);
        PetitionEvent::factory()
            ->recycle($petition)
            ->withType(PetitionEventType::SUSPENSION_END)
            ->create(['date' => CalendarDate::today()->subDay(), 'duration' => null]);

        $this->assertSame([], $this->usedParticularities($department));
    }

    /**
     * @return array<int, string>
     */
    private function usedParticularities(Department $department): array
    {
        return $this->app->make(PetitionIndexService::class)
            ->getUsedPetitionParticularities($department)
            ->all();
    }

    private function relatePetitionWithLabel(Petition $petition, string $label): void
    {
        $petition->relatedPetitions()->attach($this->createPetitionWithLabel($label));
    }

    private function createPetitionWithLabel(string $label): Petition
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['particularity_label' => $label]);

        return Petition::factory()->recycle($department)->recycle($petitionType)->create();
    }
}
