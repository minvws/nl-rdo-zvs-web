<?php

declare(strict_types=1);

namespace Tests\Feature\Collections;

use App\Collections\PetitionEventCollection;
use App\Enums\PetitionEventType;
use App\Enums\ResultType;
use App\Models\Petition;
use App\Models\PetitionEvent;
use Tests\Feature\FeatureTestCase;

class PetitionEventCollectionTest extends FeatureTestCase
{
    public function testPetitionEventsUseTheCollection(): void
    {
        $petition = Petition::factory()->create();

        $this->assertInstanceOf(PetitionEventCollection::class, $petition->petitionEvents);
    }

    public function testFinalResultReturnsTheFinalResultEvent(): void
    {
        $petition = $this->createPetitionWithFinalResult(ResultType::WITHDRAWN);

        $this->assertSame(ResultType::WITHDRAWN, $petition->petitionEvents->finalResult()?->result_type);
    }

    public function testFinalResultWithMatchingResultType(): void
    {
        $petition = $this->createPetitionWithFinalResult(ResultType::WITHDRAWN);

        $this->assertNotNull($petition->petitionEvents->finalResult(ResultType::WITHDRAWN));
    }

    public function testFinalResultWithOtherResultType(): void
    {
        $petition = $this->createPetitionWithFinalResult(ResultType::WITHDRAWN);

        $this->assertNull($petition->petitionEvents->finalResult(ResultType::FINAL_DECISION));
    }

    public function testFinalResultWithoutFinalResultEvent(): void
    {
        $petition = Petition::factory()->create();
        PetitionEvent::factory()->create([
            'petition_id' => $petition->id,
            'type' => PetitionEventType::RECEIPT_OF_OBJECTION,
        ]);

        $this->assertNull($petition->petitionEvents->finalResult());
        $this->assertNull($petition->petitionEvents->finalResultWithoutDecision());
    }

    public function testFinalResultWithoutDecisionWithOtherResultType(): void
    {
        $petition = $this->createPetitionWithFinalResult(ResultType::FORWARDED);

        $this->assertSame(ResultType::FORWARDED, $petition->petitionEvents->finalResultWithoutDecision()?->result_type);
    }

    public function testFinalResultWithoutDecisionWithFinalDecision(): void
    {
        $petition = $this->createPetitionWithFinalResult(ResultType::FINAL_DECISION);

        $this->assertNull($petition->petitionEvents->finalResultWithoutDecision());
    }

    private function createPetitionWithFinalResult(ResultType $resultType): Petition
    {
        $petition = Petition::factory()->create();
        PetitionEvent::factory()->create([
            'petition_id' => $petition->id,
            'type' => PetitionEventType::FINAL_RESULT,
            'result_type' => $resultType,
        ]);

        return $petition;
    }
}
