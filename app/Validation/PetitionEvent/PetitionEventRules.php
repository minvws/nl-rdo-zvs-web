<?php

declare(strict_types=1);

namespace App\Validation\PetitionEvent;

use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\Validation\Rules\DateMustBeAfterDependencyRule;
use App\Validation\Rules\DateMustBeInSuspensionRule;
use App\Validation\Rules\DateMustBeInTermRule;
use App\Validation\Rules\DateMustBeLatestEventRule;
use App\Validation\Rules\DateMustNotBeAfterStatutoryTermRule;
use App\Validation\Rules\DateMustNotBeInSuspensionRule;
use App\Validation\Rules\DateMustNotBeInTheFutureRule;
use App\Validation\Rules\FitsOpenEventsRule;
use App\Validation\Rules\LastEventMustBeOneOfRule;
use App\Validation\Rules\NextDayMustBeInTermRule;
use App\Validation\Rules\RequiresDependencyRule;
use App\Validation\Rules\UniquenessRule;
use App\Validation\Rules\ValidationRuleInterface;
use App\ValueObjects\PetitionEventData;

final class PetitionEventRules
{
    /**
     * Runs the rules for the event type in order and returns the first failure, or a valid result.
     */
    public static function validate(PetitionEventType $type, PetitionEventData $event, DerivedState $state): ValidationResult
    {
        foreach (self::for($type) as $rule) {
            $result = $rule->validate($event, $state);

            if ($result !== null) {
                return $result;
            }
        }

        return new ValidationResult();
    }

    /**
     * Exhaustive by design: a new PetitionEventType without an arm here fails with an UnhandledMatchError
     * instead of silently skipping validation. Use an explicit empty array for types without rules.
     *
     * @return array<int, ValidationRuleInterface>
     */
    public static function for(PetitionEventType $type): array
    {
        return match ($type) {
            PetitionEventType::PRIMARY_DECISION => [
                new UniquenessRule(),
            ],

            PetitionEventType::RECEIPT_OF_OBJECTION => [
                new RequiresDependencyRule(PetitionEventType::PRIMARY_DECISION),
            ],

            PetitionEventType::RECEIPT_OF_COMPLAINT => [
                new UniquenessRule(),
            ],

            PetitionEventType::PETITION_RECEIVED => [
                new UniquenessRule(),
            ],

            PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED => [
                new RequiresDependencyRule([
                    PetitionEventType::RECEIPT_OF_OBJECTION,
                    PetitionEventType::PETITION_RECEIVED,
                ]),
                new DateMustBeLatestEventRule(),
            ],

            PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN => [
                new LastEventMustBeOneOfRule([PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED]),
                new DateMustBeLatestEventRule(),
            ],

            PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY => [
                new RequiresDependencyRule([
                    PetitionEventType::RECEIPT_OF_OBJECTION,
                    PetitionEventType::PETITION_RECEIVED,
                ]),
                new DateMustBeLatestEventRule(),
            ],

            PetitionEventType::APPEAL_DECISION_NOT_TIMELY => [
                new RequiresDependencyRule(PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY),
                new FitsOpenEventsRule(),
                new DateMustBeLatestEventRule(),
            ],

            PetitionEventType::FINAL_RESULT => [
                new DateMustBeLatestEventRule(),
                new RequiresDependencyRule([
                    PetitionEventType::RECEIPT_OF_OBJECTION,
                    PetitionEventType::PETITION_RECEIVED,
                    PetitionEventType::RECEIPT_OF_COMPLAINT,
                ]),
                new UniquenessRule(),
            ],

            PetitionEventType::LETTER_OF_SUSPENSION_SENT => [
                new RequiresDependencyRule([
                    PetitionEventType::RECEIPT_OF_OBJECTION,
                    PetitionEventType::PETITION_RECEIVED,
                ]),
                new DateMustBeLatestEventRule(),
                new DateMustNotBeInSuspensionRule(),
                new NextDayMustBeInTermRule([TermType::OBJECTION_PERIOD, TermType::DECISION_PERIOD]),
            ],

            PetitionEventType::SUSPENSION_END => [
                new DateMustBeLatestEventRule(),
                new LastEventMustBeOneOfRule([
                    PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                    PetitionEventType::MEETING_SCHEDULED,
                    PetitionEventType::ADJOURNMENT,
                ]),
                new DateMustBeInSuspensionRule(),
            ],

            PetitionEventType::MEETING_SCHEDULED => [
                new DateMustBeLatestEventRule(),
                new UniquenessRule(),
                new DateMustBeInTermRule([TermType::OBJECTION_PERIOD, TermType::DECISION_PERIOD]),
                new DateMustNotBeAfterStatutoryTermRule(),
            ],

            PetitionEventType::ADJOURNMENT => [
                new DateMustBeLatestEventRule(),
                new UniquenessRule(),
                new DateMustBeInTermRule([TermType::DECISION_PERIOD]),
                new DateMustNotBeAfterStatutoryTermRule(),
            ],

            PetitionEventType::COMMITTEE_HEARING_DETERMINED => [
                new DateMustBeLatestEventRule(),
                new UniquenessRule(),
                new DateMustBeInTermRule([TermType::OBJECTION_PERIOD, TermType::DECISION_PERIOD]),
                new DateMustNotBeAfterStatutoryTermRule(),
            ],

            PetitionEventType::HEARING_DATE => [
                new DateMustBeLatestEventRule(),
                new RequiresDependencyRule([
                    PetitionEventType::RECEIPT_OF_OBJECTION,
                    PetitionEventType::PETITION_RECEIVED,
                ]),
            ],

            PetitionEventType::UNSPECIFIED_ADJOURNMENT => [
                new RequiresDependencyRule([
                    PetitionEventType::RECEIPT_OF_OBJECTION,
                    PetitionEventType::PETITION_RECEIVED,
                ]),
                new DateMustBeLatestEventRule(),
                new DateMustBeInTermRule([TermType::OBJECTION_PERIOD, TermType::DECISION_PERIOD]),
                new DateMustNotBeInSuspensionRule(),
                new DateMustNotBeInTheFutureRule(),
            ],

            PetitionEventType::UNSPECIFIED_ADJOURNMENT_END => [
                new RequiresDependencyRule(PetitionEventType::UNSPECIFIED_ADJOURNMENT),
                new DateMustBeAfterDependencyRule(PetitionEventType::UNSPECIFIED_ADJOURNMENT),
                new DateMustBeLatestEventRule(),
                new DateMustNotBeInTheFutureRule(),
            ],

            PetitionEventType::ACTUAL_DISCLOSURE => [
                new DateMustBeLatestEventRule(),
            ],

            PetitionEventType::PUBLICATION_DATE => [
                new DateMustBeLatestEventRule(),
            ],

            PetitionEventType::OPINION_OUTSIDE_TERM => [
                new DateMustBeLatestEventRule(),
            ],

            PetitionEventType::SENT_PARTIAL_DECISION => [],

            PetitionEventType::REFERRED_TO_DEPARTMENT => [
                new RequiresDependencyRule([PetitionEventType::PETITION_RECEIVED]),
                new FitsOpenEventsRule(),
                new DateMustBeLatestEventRule(),
            ],

            PetitionEventType::ANSWER_RECEIVED => [
                new RequiresDependencyRule([PetitionEventType::REFERRED_TO_DEPARTMENT]),
                new FitsOpenEventsRule(),
                new DateMustBeLatestEventRule(),
            ],
        };
    }
}
