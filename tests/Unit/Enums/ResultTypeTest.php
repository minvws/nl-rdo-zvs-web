<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\PetitionEventType;
use App\Enums\PetitionVariant;
use App\Enums\ResultType;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function __;
use function sprintf;

class ResultTypeTest extends TestCase
{
    #[Test]
    public function testAllowedFollowUpEventTypesForFinalDecision(): void
    {
        $types = ResultType::FINAL_DECISION->allowedFollowUpEventTypes();

        $this->assertContains(PetitionEventType::ACTUAL_DISCLOSURE, $types);
        $this->assertContains(PetitionEventType::PUBLICATION_DATE, $types);
        $this->assertCount(2, $types);
    }

    #[Test]
    public function testAllowedFollowUpEventTypesForFinalDecision55Request(): void
    {
        $types = ResultType::FINAL_DECISION_55_REQUEST->allowedFollowUpEventTypes();

        $this->assertContains(PetitionEventType::ACTUAL_DISCLOSURE, $types);
        $this->assertNotContains(PetitionEventType::PUBLICATION_DATE, $types);
        $this->assertCount(1, $types);
    }

    #[Test]
    public function testAllowedFollowUpEventTypesForRejected(): void
    {
        $types = ResultType::REJECTED->allowedFollowUpEventTypes();

        $this->assertNotContains(PetitionEventType::ACTUAL_DISCLOSURE, $types);
        $this->assertContains(PetitionEventType::PUBLICATION_DATE, $types);
        $this->assertCount(1, $types);
    }

    #[Test]
    public function testAllowedFollowUpEventTypesEmptyForZonderBesluit(): void
    {
        foreach ([ResultType::WITHDRAWN, ResultType::FORWARDED, ResultType::DISMISSED, ResultType::RECONSIDERED, ResultType::ALREADY_PUBLIC, ResultType::OTHER] as $type) {
            $this->assertEmpty($type->allowedFollowUpEventTypes(), sprintf('Expected empty for %s', $type->value));
        }
    }

    #[Test]
    public function testFinalDecisionLabel(): void
    {
        $label = ResultType::FINAL_DECISION->label();

        $this->assertEquals(__('result_type.default.final_decision'), $label);
    }

    #[Test]
    public function testFinalDecision55RequestLabel(): void
    {
        $label = ResultType::FINAL_DECISION_55_REQUEST->label();

        $this->assertEquals(__('result_type.default.final_decision_55_request'), $label);
    }

    #[Test]
    public function testWithdrawnLabel(): void
    {
        $label = ResultType::WITHDRAWN->label();

        $this->assertEquals(__('result_type.default.withdrawn'), $label);
    }

    #[Test]
    public function testForwardedLabel(): void
    {
        $label = ResultType::FORWARDED->label();

        $this->assertEquals(__('result_type.default.forwarded'), $label);
    }

    #[Test]
    public function testRequiresReasoningIsTrueOnlyForOther(): void
    {
        $this->assertTrue(ResultType::OTHER->requiresReasoning());
        $this->assertFalse(ResultType::FINAL_DECISION->requiresReasoning());
    }

    #[Test]
    public function testAllCasesHaveLabels(): void
    {
        foreach (ResultType::cases() as $type) {
            $this->assertNotEmpty($type->label());
        }
    }

    #[Test]
    public function testGetForPetitionTypeBezwaar(): void
    {
        $types = ResultType::getForPetitionType(PetitionVariant::BEZWAAR);

        $this->assertCount(3, $types);
        $this->assertContains(ResultType::FINAL_DECISION, $types);
        $this->assertContains(ResultType::WITHDRAWN, $types);
        $this->assertContains(ResultType::FORWARDED, $types);
    }

    #[Test]
    public function testGetForPetitionTypeWooVerzoek(): void
    {
        $types = ResultType::getForPetitionType(PetitionVariant::WOO_VERZOEK);

        $this->assertCount(9, $types);
        $this->assertContains(ResultType::FINAL_DECISION, $types);
        $this->assertContains(ResultType::FINAL_DECISION_55_REQUEST, $types);
        $this->assertContains(ResultType::WITHDRAWN, $types);
        $this->assertContains(ResultType::FORWARDED, $types);
        $this->assertContains(ResultType::REJECTED, $types);
        $this->assertContains(ResultType::DISMISSED, $types);
        $this->assertContains(ResultType::RECONSIDERED, $types);
        $this->assertContains(ResultType::ALREADY_PUBLIC, $types);
        $this->assertContains(ResultType::OTHER, $types);
    }

    #[Test]
    public function testGetForPetitionTypeBeroep(): void
    {
        $types = ResultType::getForPetitionType(PetitionVariant::BEROEP);

        $this->assertEmpty($types);
    }

    #[Test]
    public function testGetGroupedForPetitionTypeWooVerzoek(): void
    {
        $grouped = ResultType::getGroupedForPetitionType(PetitionVariant::WOO_VERZOEK);

        $this->assertArrayHasKey('with', $grouped);
        $this->assertArrayHasKey('without', $grouped);
        $this->assertContains(ResultType::FINAL_DECISION, $grouped['with']);
        $this->assertContains(ResultType::FINAL_DECISION_55_REQUEST, $grouped['with']);
        $this->assertContains(ResultType::REJECTED, $grouped['with']);
        $this->assertContains(ResultType::DISMISSED, $grouped['with']);
        $this->assertCount(4, $grouped['with']);
        $this->assertContains(ResultType::WITHDRAWN, $grouped['without']);
        $this->assertContains(ResultType::FORWARDED, $grouped['without']);
        $this->assertContains(ResultType::RECONSIDERED, $grouped['without']);
        $this->assertContains(ResultType::ALREADY_PUBLIC, $grouped['without']);
        $this->assertContains(ResultType::OTHER, $grouped['without']);
        $this->assertCount(5, $grouped['without']);
    }

    #[Test]
    public function testGetGroupedForPetitionTypeBezwaar(): void
    {
        $grouped = ResultType::getGroupedForPetitionType(PetitionVariant::BEZWAAR);

        $this->assertArrayHasKey('with', $grouped);
        $this->assertArrayHasKey('without', $grouped);
        $this->assertContains(ResultType::FINAL_DECISION, $grouped['with']);
        $this->assertCount(1, $grouped['with']);
        $this->assertContains(ResultType::WITHDRAWN, $grouped['without']);
        $this->assertContains(ResultType::FORWARDED, $grouped['without']);
        $this->assertCount(2, $grouped['without']);
    }

    #[Test]
    public function testGetGroupedForPetitionTypeDefault(): void
    {
        $grouped = ResultType::getGroupedForPetitionType(PetitionVariant::BEROEP);

        $this->assertEmpty($grouped['with']);
        $this->assertEmpty($grouped['without']);
    }
}
