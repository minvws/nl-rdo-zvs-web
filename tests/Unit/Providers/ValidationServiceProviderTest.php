<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use App\Enums\PetitionEventType;
use App\Services\EventValidatorInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function app;
use function sprintf;

class ValidationServiceProviderTest extends TestCase
{
    #[Test]
    public function testAllConfiguredEventTypesHaveBindings(): void
    {
        $configured = [
            PetitionEventType::PRIMARY_DECISION,
            PetitionEventType::PETITION_RECEIVED,
            PetitionEventType::RECEIPT_OF_OBJECTION,
            PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
            PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
            PetitionEventType::FINAL_RESULT,
            PetitionEventType::LETTER_OF_SUSPENSION_SENT,
            PetitionEventType::SUSPENSION_END,
            PetitionEventType::MEETING_SCHEDULED,
            PetitionEventType::ADJOURNMENT,
            PetitionEventType::HEARING_DATE,
            PetitionEventType::UNSPECIFIED_ADJOURNMENT,
            PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
            PetitionEventType::ACTUAL_DISCLOSURE,
            PetitionEventType::PUBLICATION_DATE,
            PetitionEventType::OPINION_OUTSIDE_TERM,
            PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY,
            PetitionEventType::SENT_PARTIAL_DECISION,
        ];

        foreach ($configured as $type) {
            $key = 'validation.rule.' . $type->value;
            $this->assertTrue(app()->has($key), sprintf('Container missing binding for %s', $type->value));

            $validator = $type->rule();
            $this->assertInstanceOf(
                EventValidatorInterface::class,
                $validator,
                sprintf('Rule() did not resolve validator for %s', $type->value),
            );
        }
    }
}
