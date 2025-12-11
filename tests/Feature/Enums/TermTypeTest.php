<?php

declare(strict_types=1);

namespace Tests\Feature\Enums;

use App\Enums\TermType;
use Tests\Feature\FeatureTestCase;

use function count;
use function sprintf;

class TermTypeTest extends FeatureTestCase
{
    public function testSpecificTermTypesAreDeadlineable(): void
    {
        $deadlineableTypes = [
            TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_EVENT,
            TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_WITHDRAWAL,
            TermType::PENDING_TERM_AFTER_EVENT,
            TermType::PENDING_TERM_AFTER_WITHDRAWAL,
        ];

        foreach ($deadlineableTypes as $termType) {
            $this->assertTrue(
                $termType->isDeadlineable(),
                sprintf("TermType::%s should be deadlineable", $termType->name),
            );
        }
    }

    public function testAllExpectedTermTypesAreDeadlineable(): void
    {
        $expectedDeadlineableTypes = [
            TermType::FIRST,
            TermType::SECOND,
            TermType::THIRD,
            TermType::NOTICE_OF_DEFAULT,
            TermType::APPEAL_NOT_TIMELY,
            TermType::OBJECTION_PERIOD,
            TermType::COMMITTEE_HEARING,
            TermType::SPECIFIED_ADJOURNMENT,
            TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_EVENT,
            TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_WITHDRAWAL,
            TermType::PENDING_TERM_AFTER_EVENT,
            TermType::PENDING_TERM_AFTER_WITHDRAWAL,
        ];

        foreach ($expectedDeadlineableTypes as $termType) {
            $this->assertTrue(
                $termType->isDeadlineable(),
                sprintf("TermType::%s should be deadlineable", $termType->name),
            );
        }
    }

    public function testNonDeadlineableTermTypes(): void
    {
        $nonDeadlineableTypes = [
            TermType::SUSPENSION,
            TermType::PENALTY,
        ];

        foreach ($nonDeadlineableTypes as $termType) {
            $this->assertFalse(
                $termType->isDeadlineable(),
                sprintf("TermType::%s should not be deadlineable", $termType->name),
            );
        }
    }

    public function testAllTermTypesHaveExplicitDeadlineableBehavior(): void
    {
        $allTermTypes = TermType::cases();
        $deadlineableCount = 0;
        $nonDeadlineableCount = 0;

        foreach ($allTermTypes as $termType) {
            if ($termType->isDeadlineable()) {
                $deadlineableCount++;
            } else {
                $nonDeadlineableCount++;
            }
        }

        $this->assertEquals(count($allTermTypes), $deadlineableCount + $nonDeadlineableCount);
        $this->assertGreaterThan(0, $deadlineableCount, 'There should be at least some deadlineable term types');
        $this->assertGreaterThan(0, $nonDeadlineableCount, 'There should be at least some non-deadlineable term types');
    }
}
