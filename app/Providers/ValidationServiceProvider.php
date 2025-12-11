<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\Validation\PetitionEvent\ComposableValidator;
use App\Validation\Rules\DateMustBeAfterDependencyRule;
use App\Validation\Rules\DateMustBeInSuspensionRule;
use App\Validation\Rules\DateMustBeInTermRule;
use App\Validation\Rules\DateMustBeLatestEventRule;
use App\Validation\Rules\DateMustNotBeInSuspensionRule;
use App\Validation\Rules\DateMustNotBeInTermRule;
use App\Validation\Rules\LastEventMustBeOneOfRule;
use App\Validation\Rules\NextDayMustBeInTermRule;
use App\Validation\Rules\RequiresDependencyRule;
use App\Validation\Rules\UniquenessRule;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Override;

class ValidationServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton(DateMustBeLatestEventRule::class);
        $this->app->singleton(UniquenessRule::class);

        $this->registerValidators();
    }

    private function registerValidators(): void
    {
        $this->registerPrimaryDecisionValidator();
        $this->registerReceiptOfObjectionValidator();
        $this->registerNoticeOfDefaultReceivedValidator();
        $this->registerReceiptAppealNotTimelyValidator();
        $this->registerAppealDecisionNotTimelyValidator();
        $this->registerFinalResultValidator();
        $this->registerLetterOfSuspensionSentValidator();
        $this->registerSuspensionEndValidator();
        $this->registerCommitteeHearingScheduledValidator();
        $this->registerAdjournmentValidator();
        $this->registerHearingDateValidator();
        $this->registerUnspecifiedAdjournmentValidator();
        $this->registerUnspecifiedAdjournmentEndValidator();
        $this->registerPetitionReceivedValidator();
        $this->registerActualDisclosureValidator();
        $this->registerPublicationDateValidator();
        $this->registerOpinionOutsideTermValidator();
        $this->registerSentPartialDecisionValidator();
    }

    private function registerPrimaryDecisionValidator(): void
    {
        $this->app->bind(
            'validation.rule.' . PetitionEventType::PRIMARY_DECISION->value,
            static function (Application $app): ComposableValidator {
                return new ComposableValidator([
                    $app->make(UniquenessRule::class),
                ]);
            },
        );
    }

    private function registerReceiptOfObjectionValidator(): void
    {
        $this->app->bind(
            'validation.rule.' . PetitionEventType::RECEIPT_OF_OBJECTION->value,
            static function (Application $app): ComposableValidator {
                return new ComposableValidator([
                    new RequiresDependencyRule(PetitionEventType::PRIMARY_DECISION),
                ]);
            },
        );
    }

    private function registerNoticeOfDefaultReceivedValidator(): void
    {
        $this->app->bind(
            'validation.rule.' . PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->value,
            static function (Application $app): ComposableValidator {
                return new ComposableValidator([
                    new RequiresDependencyRule([
                        PetitionEventType::RECEIPT_OF_OBJECTION,
                        PetitionEventType::PETITION_RECEIVED,
                    ]),
                    $app->make(DateMustBeLatestEventRule::class),
                    new DateMustNotBeInTermRule([TermType::OBJECTION_PERIOD->value, TermType::DECISION_PERIOD->value]),
                ]);
            },
        );
    }

    private function registerReceiptAppealNotTimelyValidator(): void
    {
        $this->app->bind(
            'validation.rule.' . PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY->value,
            static function (Application $app): ComposableValidator {
                return new ComposableValidator([
                    new RequiresDependencyRule(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED),
                    $app->make(DateMustBeLatestEventRule::class),
                    new DateMustNotBeInTermRule([
                        TermType::NOTICE_OF_DEFAULT->value,
                        TermType::APPEAL_NOT_TIMELY->value,
                        TermType::PENALTY_PERIOD->value,
                    ]),
                ]);
            },
        );
    }

    private function registerAppealDecisionNotTimelyValidator(): void
    {
        $this->app->bind(
            'validation.rule.' . PetitionEventType::APPEAL_DECISION_NOT_TIMELY->value,
            static function (Application $app): ComposableValidator {
                return new ComposableValidator([
                    new RequiresDependencyRule(PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY),
                    $app->make(DateMustBeLatestEventRule::class),
                    new DateMustNotBeInTermRule([
                        TermType::NOTICE_OF_DEFAULT->value,
                        TermType::APPEAL_NOT_TIMELY->value,
                        TermType::PENALTY_PERIOD->value,
                    ]),
                ]);
            },
        );
    }

    private function registerFinalResultValidator(): void
    {
        $this->app->bind(
            'validation.rule.' . PetitionEventType::FINAL_RESULT->value,
            static function (Application $app): ComposableValidator {
                return new ComposableValidator([
                    $app->make(DateMustBeLatestEventRule::class),
                    new RequiresDependencyRule([
                        PetitionEventType::RECEIPT_OF_OBJECTION,
                        PetitionEventType::PETITION_RECEIVED,
                    ]),
                    $app->make(UniquenessRule::class),
                ]);
            },
        );
    }

    private function registerLetterOfSuspensionSentValidator(): void
    {
        $this->app->bind(
            'validation.rule.' . PetitionEventType::LETTER_OF_SUSPENSION_SENT->value,
            static function (Application $app): ComposableValidator {
                return new ComposableValidator([
                    new RequiresDependencyRule([
                        PetitionEventType::RECEIPT_OF_OBJECTION,
                        PetitionEventType::PETITION_RECEIVED,
                    ]),
                    $app->make(DateMustBeLatestEventRule::class),
                    new DateMustNotBeInSuspensionRule(),
                    new NextDayMustBeInTermRule([TermType::OBJECTION_PERIOD->value, TermType::DECISION_PERIOD->value]),
                ]);
            },
        );
    }

    private function registerSuspensionEndValidator(): void
    {
        $this->app->bind(
            'validation.rule.' . PetitionEventType::SUSPENSION_END->value,
            static function (Application $app): ComposableValidator {
                return new ComposableValidator([
                    $app->make(DateMustBeLatestEventRule::class),
                    new LastEventMustBeOneOfRule([
                        PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                        PetitionEventType::MEETING_SCHEDULED,
                    ]),
                    new DateMustBeInSuspensionRule(),
                ]);
            },
        );
    }

    private function registerCommitteeHearingScheduledValidator(): void
    {
        $this->app->bind(
            'validation.rule.' . PetitionEventType::MEETING_SCHEDULED->value,
            static function (Application $app): ComposableValidator {
                return new ComposableValidator([
                    $app->make(DateMustBeLatestEventRule::class),
                    $app->make(UniquenessRule::class),
                    new DateMustBeInTermRule([TermType::OBJECTION_PERIOD->value, TermType::DECISION_PERIOD->value]),
                ]);
            },
        );
    }

    private function registerAdjournmentValidator(): void
    {
        $this->app->bind(
            'validation.rule.' . PetitionEventType::ADJOURNMENT->value,
            static function (Application $app): ComposableValidator {
                return new ComposableValidator([
                    $app->make(DateMustBeLatestEventRule::class),
                    $app->make(UniquenessRule::class),
                    new DateMustBeInTermRule([TermType::OBJECTION_PERIOD->value, TermType::DECISION_PERIOD->value]),
                ]);
            },
        );
    }

    private function registerHearingDateValidator(): void
    {
        $this->app->bind(
            'validation.rule.' . PetitionEventType::HEARING_DATE->value,
            static function (Application $app): ComposableValidator {
                return new ComposableValidator([
                    $app->make(DateMustBeLatestEventRule::class),
                    new RequiresDependencyRule([
                        PetitionEventType::RECEIPT_OF_OBJECTION,
                        PetitionEventType::PETITION_RECEIVED,
                    ]),
                    $app->make(UniquenessRule::class),
                ]);
            },
        );
    }

    private function registerUnspecifiedAdjournmentValidator(): void
    {
        $this->app->bind(
            'validation.rule.' . PetitionEventType::UNSPECIFIED_ADJOURNMENT->value,
            static function (Application $app): ComposableValidator {
                return new ComposableValidator([
                    $app->make(UniquenessRule::class),
                    new RequiresDependencyRule([
                        PetitionEventType::RECEIPT_OF_OBJECTION,
                        PetitionEventType::PETITION_RECEIVED,
                    ]),
                    $app->make(DateMustBeLatestEventRule::class),
                    new DateMustBeInTermRule([TermType::DECISION_PERIOD->value]),
                    new DateMustNotBeInSuspensionRule(),
                ]);
            },
        );
    }

    private function registerUnspecifiedAdjournmentEndValidator(): void
    {
        $this->app->bind(
            'validation.rule.' . PetitionEventType::UNSPECIFIED_ADJOURNMENT_END->value,
            static function (Application $app): ComposableValidator {
                return new ComposableValidator([
                    new RequiresDependencyRule(PetitionEventType::UNSPECIFIED_ADJOURNMENT),
                    $app->make(UniquenessRule::class),
                    new DateMustBeAfterDependencyRule(PetitionEventType::UNSPECIFIED_ADJOURNMENT),
                    $app->make(DateMustBeLatestEventRule::class),
                ]);
            },
        );
    }

    private function registerPetitionReceivedValidator(): void
    {
        $this->app->bind(
            'validation.rule.' . PetitionEventType::PETITION_RECEIVED->value,
            static function (Application $app): ComposableValidator {
                return new ComposableValidator([
                    $app->make(UniquenessRule::class),
                ]);
            },
        );
    }

    private function registerActualDisclosureValidator(): void
    {
        $this->app->bind(
            'validation.rule.' . PetitionEventType::ACTUAL_DISCLOSURE->value,
            static function (Application $app): ComposableValidator {
                return new ComposableValidator([
                    $app->make(DateMustBeLatestEventRule::class),
                ]);
            },
        );
    }

    private function registerPublicationDateValidator(): void
    {
        $this->app->bind(
            'validation.rule.' . PetitionEventType::PUBLICATION_DATE->value,
            static function (Application $app): ComposableValidator {
                return new ComposableValidator([
                    $app->make(DateMustBeLatestEventRule::class),
                ]);
            },
        );
    }

    private function registerOpinionOutsideTermValidator(): void
    {
        $this->app->bind(
            'validation.rule.' . PetitionEventType::OPINION_OUTSIDE_TERM->value,
            static function (Application $app): ComposableValidator {
                return new ComposableValidator([
                    $app->make(DateMustBeLatestEventRule::class),
                ]);
            },
        );
    }

    private function registerSentPartialDecisionValidator(): void
    {
        $this->app->bind(
            'validation.rule.' . PetitionEventType::SENT_PARTIAL_DECISION->value,
            static function (Application $app): ComposableValidator {
                return new ComposableValidator([
                    $app->make(DateMustBeLatestEventRule::class),
                ]);
            },
        );
    }
}
