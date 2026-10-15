<?php

declare(strict_types=1);

namespace App\Enums;

use App\ValueObjects\WizardEventCollection;
use BackedEnum;
use Illuminate\Support\Facades\Lang;

use function sprintf;

/**
 * @SuppressWarnings(PHPMD)
 */
enum PetitionEventType: string
{
    case PRIMARY_DECISION = 'primary_decision';
    case RECEIPT_OF_OBJECTION = 'receipt_of_objection';
    case PETITION_RECEIVED = 'petition_received';
    case LETTER_OF_SUSPENSION_SENT = 'letter_of_suspension_sent';
    case SUSPENSION_END = 'suspension_end';
    case MEETING_SCHEDULED = 'meeting_scheduled'; // committee_hearing_scheduled or appointment_with_applicant_scheduled
    case ADJOURNMENT = 'adjournment';
    case COMMITTEE_HEARING_DETERMINED = 'committee_hearing_determined';
    case UNSPECIFIED_ADJOURNMENT = 'unspecified_adjournment';
    case UNSPECIFIED_ADJOURNMENT_END = 'unspecified_adjournment_end';
    case HEARING_DATE = 'hearing_date';
    case NOTICE_OF_DEFAULT_RECEIVED = 'notice_of_default_received';
    case NOTICE_OF_DEFAULT_WITHDRAWN = 'notice_of_default_withdrawn';
    case APPEAL_DECISION_NOT_TIMELY = 'appeal_decision_not_timely';
    case FINAL_RESULT = 'final_result';
    case ACTUAL_DISCLOSURE = 'actual_disclosure';
    case PUBLICATION_DATE = 'publication_date';
    case OPINION_OUTSIDE_TERM = 'opinion_outside_term';
    case RECEIPT_APPEAL_NOT_TIMELY = 'receipt_appeal_not_timely';
    case SENT_PARTIAL_DECISION = 'sent_partial_decision';
    case RECEIPT_OF_COMPLAINT = 'receipt_of_complaint';
    case REFERRED_TO_DEPARTMENT = 'referred_to_department';
    case ANSWER_RECEIVED = 'answer_received';

    /**
     * @codeCoverageIgnore pure config mapping, no business logic to test
     */
    public function hasEndDate(): bool
    {
        return match ($this) {
            self::MEETING_SCHEDULED => true,
            self::APPEAL_DECISION_NOT_TIMELY => true,
            default => false,
        };
    }

    /**
     * @codeCoverageIgnore pure config mapping, no business logic to test
     */
    public function hasDuration(): bool
    {
        // An end date also procures a duration in days, the form field to get it is different.
        if ($this->hasEndDate()) {
            return true;
        }

        return match ($this) {
            self::PRIMARY_DECISION,
            self::RECEIPT_OF_OBJECTION,
            self::RECEIPT_OF_COMPLAINT,
            self::LETTER_OF_SUSPENSION_SENT,
            self::ADJOURNMENT,
            self::COMMITTEE_HEARING_DETERMINED,
            self::NOTICE_OF_DEFAULT_RECEIVED,
            self::PETITION_RECEIVED, => true,
            default => false,
        };
    }

    /**
     * Departments on UnspecifiedAdjournmentMode::CONTINUES_TERMS register two decision terms when
     * an "ongespecificeerde aanhouding" starts: one that runs once the event has taken place and
     * one that runs once the adjournment has been withdrawn.
     */
    public function hasWithdrawalDuration(): bool
    {
        return $this === self::UNSPECIFIED_ADJOURNMENT;
    }

    public function hasPenalties(?PetitionVariant $petitionType = null): bool
    {
        return match ($this) {
            self::NOTICE_OF_DEFAULT_RECEIVED => $petitionType === PetitionVariant::BEZWAAR,
            self::APPEAL_DECISION_NOT_TIMELY => true,
            default => false,
        };
    }

    public function hasSuspensionType(): bool
    {
        return match ($this) {
            self::LETTER_OF_SUSPENSION_SENT => true,
            default => false,
        };
    }

    public function hasResultType(): bool
    {
        return match ($this) {
            self::FINAL_RESULT => true,
            default => false,
        };
    }

    public function hasHearingForm(): bool
    {
        return match ($this) {
            self::HEARING_DATE => true,
            default => false,
        };
    }

    public function hasReasoning(): bool
    {
        if ($this->hasReasoningSelect()) {
            return true;
        }

        return $this->hasReasoningTextarea();
    }

    public function hasReasoningSelect(): bool
    {
        return $this->reasoningSelectEnumClass() !== null;
    }

    /**
     * @return class-string<BackedEnum>|null
     */
    public function reasoningSelectEnumClass(): ?string
    {
        return match ($this) {
            self::UNSPECIFIED_ADJOURNMENT_END => AdjournmentEndReason::class,
            default => null,
        };
    }

    public function hasReasoningTextarea(): bool
    {
        return match ($this) {
            self::FINAL_RESULT, self::UNSPECIFIED_ADJOURNMENT => true,
            default => false,
        };
    }

    public function requiresReasoning(): bool
    {
        return match ($this) {
            self::UNSPECIFIED_ADJOURNMENT_END => true,
            default => false,
        };
    }

    public function isAvailableFor(PetitionVariant $petitionType): bool
    {
        return match ($this) {
            self::PRIMARY_DECISION,
            self::RECEIPT_OF_OBJECTION,
            self::COMMITTEE_HEARING_DETERMINED,
            self::HEARING_DATE => $petitionType === PetitionVariant::BEZWAAR,

            self::PETITION_RECEIVED,
            self::PUBLICATION_DATE,
            self::ACTUAL_DISCLOSURE,
            self::OPINION_OUTSIDE_TERM,
            self::SENT_PARTIAL_DECISION,
            self::REFERRED_TO_DEPARTMENT,
            self::ANSWER_RECEIVED => $petitionType === PetitionVariant::WOO_VERZOEK,

            self::NOTICE_OF_DEFAULT_WITHDRAWN => match ($petitionType) {
                PetitionVariant::BEZWAAR, PetitionVariant::WOO_VERZOEK => true,
                default => false,
            },

            self::LETTER_OF_SUSPENSION_SENT,
            self::SUSPENSION_END,
            self::NOTICE_OF_DEFAULT_RECEIVED,
            self::APPEAL_DECISION_NOT_TIMELY,
            self::RECEIPT_APPEAL_NOT_TIMELY,
            self::UNSPECIFIED_ADJOURNMENT,
            self::UNSPECIFIED_ADJOURNMENT_END => match ($petitionType) {
                PetitionVariant::KLACHT => false,
                default => true,
            },

            self::RECEIPT_OF_COMPLAINT => $petitionType === PetitionVariant::KLACHT,

            self::MEETING_SCHEDULED,
            self::FINAL_RESULT,
            self::ADJOURNMENT => true,
        };
    }

    /**
     * Get required event types that must exist before this event can be added (enablers).
     *
     * @return array<PetitionEventType>
     */
    public function getDependencies(PetitionVariant $petitionType): array
    {
        return match ($this) {
            self::ACTUAL_DISCLOSURE => [self::FINAL_RESULT, self::SENT_PARTIAL_DECISION],

            self::PUBLICATION_DATE => [self::ACTUAL_DISCLOSURE, self::FINAL_RESULT],

            self::RECEIPT_OF_OBJECTION => [self::PRIMARY_DECISION],

            self::LETTER_OF_SUSPENSION_SENT,
            self::NOTICE_OF_DEFAULT_RECEIVED,
            self::UNSPECIFIED_ADJOURNMENT,
            self::ADJOURNMENT,
            self::COMMITTEE_HEARING_DETERMINED,
            self::MEETING_SCHEDULED,
            self::OPINION_OUTSIDE_TERM,
            self::SENT_PARTIAL_DECISION,
            self::REFERRED_TO_DEPARTMENT,
            self::FINAL_RESULT => match ($petitionType) {
                PetitionVariant::BEZWAAR => [self::RECEIPT_OF_OBJECTION],
                PetitionVariant::WOO_VERZOEK => [self::PETITION_RECEIVED],
                PetitionVariant::KLACHT => [self::RECEIPT_OF_COMPLAINT],
                default => [],
            },

            self::RECEIPT_APPEAL_NOT_TIMELY => [
                self::RECEIPT_OF_OBJECTION,
                self::PETITION_RECEIVED,
            ],

            self::SUSPENSION_END => [self::LETTER_OF_SUSPENSION_SENT],

            self::UNSPECIFIED_ADJOURNMENT_END => [self::UNSPECIFIED_ADJOURNMENT],

            self::HEARING_DATE => match ($petitionType) {
                PetitionVariant::BEZWAAR => [self::RECEIPT_OF_OBJECTION],
                default => [],
            },
            self::NOTICE_OF_DEFAULT_WITHDRAWN => [self::NOTICE_OF_DEFAULT_RECEIVED],

            self::APPEAL_DECISION_NOT_TIMELY => [self::RECEIPT_APPEAL_NOT_TIMELY],

            self::ANSWER_RECEIVED => [self::REFERRED_TO_DEPARTMENT],

            default => [],
        };
    }

    /**
     * Get event types that prevent this event from being available (disablers).
     * If any of these events exist, this event cannot be added.
     * Disablers always take precedence over enablers.
     *
     * @return array<PetitionEventType>
     */
    public function getConflicts(PetitionVariant $petitionType): array
    {
        return match ($this) {
            self::PRIMARY_DECISION => match ($petitionType) {
                PetitionVariant::BEZWAAR => [
                    self::RECEIPT_OF_OBJECTION,
                    self::LETTER_OF_SUSPENSION_SENT,
                    self::SUSPENSION_END,
                    self::MEETING_SCHEDULED,
                    self::ADJOURNMENT,
                    self::COMMITTEE_HEARING_DETERMINED,
                    self::UNSPECIFIED_ADJOURNMENT,
                    self::UNSPECIFIED_ADJOURNMENT_END,
                    self::HEARING_DATE,
                    self::NOTICE_OF_DEFAULT_RECEIVED,
                    self::NOTICE_OF_DEFAULT_WITHDRAWN,
                    self::APPEAL_DECISION_NOT_TIMELY,
                    self::FINAL_RESULT,
                ],
                default => [],
            },
            self::RECEIPT_OF_OBJECTION, self::PETITION_RECEIVED => [
                self::PETITION_RECEIVED,
                self::LETTER_OF_SUSPENSION_SENT,
                self::SUSPENSION_END,
                self::MEETING_SCHEDULED,
                self::ADJOURNMENT,
                self::COMMITTEE_HEARING_DETERMINED,
                self::UNSPECIFIED_ADJOURNMENT,
                self::UNSPECIFIED_ADJOURNMENT_END,
                self::HEARING_DATE,
                self::NOTICE_OF_DEFAULT_RECEIVED,
                self::NOTICE_OF_DEFAULT_WITHDRAWN,
                self::APPEAL_DECISION_NOT_TIMELY,
                self::FINAL_RESULT,
            ],
            self::LETTER_OF_SUSPENSION_SENT, self::SUSPENSION_END => match ($petitionType) {
                PetitionVariant::BEZWAAR, PetitionVariant::WOO_VERZOEK => [
                    self::NOTICE_OF_DEFAULT_RECEIVED,
                    self::APPEAL_DECISION_NOT_TIMELY,
                    self::RECEIPT_APPEAL_NOT_TIMELY,
                    self::FINAL_RESULT,
                ],
                default => [],
            },
            self::NOTICE_OF_DEFAULT_RECEIVED => match ($petitionType) {
                PetitionVariant::BEZWAAR, PetitionVariant::WOO_VERZOEK => [
                    self::APPEAL_DECISION_NOT_TIMELY,
                    self::RECEIPT_APPEAL_NOT_TIMELY,
                    self::FINAL_RESULT,
                ],
                default => [],
            },
            self::MEETING_SCHEDULED => match ($petitionType) {
                PetitionVariant::BEZWAAR, PetitionVariant::WOO_VERZOEK => [
                    self::NOTICE_OF_DEFAULT_RECEIVED,
                    self::APPEAL_DECISION_NOT_TIMELY,
                    self::FINAL_RESULT,
                ],
                PetitionVariant::KLACHT => [
                    self::MEETING_SCHEDULED,
                    self::FINAL_RESULT,
                ],
                default => [],
            },
            self::ADJOURNMENT => match ($petitionType) {
                PetitionVariant::BEZWAAR, PetitionVariant::WOO_VERZOEK => [
                    self::ADJOURNMENT,
                    self::NOTICE_OF_DEFAULT_RECEIVED,
                    self::APPEAL_DECISION_NOT_TIMELY,
                    self::FINAL_RESULT,
                ],
                PetitionVariant::KLACHT => [
                    self::MEETING_SCHEDULED,
                    self::FINAL_RESULT,
                ],
                default => [],
            },
            self::COMMITTEE_HEARING_DETERMINED => match ($petitionType) {
                PetitionVariant::BEZWAAR => [
                    self::COMMITTEE_HEARING_DETERMINED,
                    self::NOTICE_OF_DEFAULT_RECEIVED,
                    self::APPEAL_DECISION_NOT_TIMELY,
                    self::FINAL_RESULT,
                ],
                default => [],
            },
            self::UNSPECIFIED_ADJOURNMENT => match ($petitionType) {
                PetitionVariant::BEZWAAR, PetitionVariant::WOO_VERZOEK => [
                    self::NOTICE_OF_DEFAULT_RECEIVED,
                    self::APPEAL_DECISION_NOT_TIMELY,
                    self::FINAL_RESULT,
                ],
                default => [],
            },
            self::UNSPECIFIED_ADJOURNMENT_END => match ($petitionType) {
                PetitionVariant::BEZWAAR, PetitionVariant::WOO_VERZOEK => [
                    self::NOTICE_OF_DEFAULT_RECEIVED,
                    self::APPEAL_DECISION_NOT_TIMELY,
                    self::FINAL_RESULT,
                ],
                default => [],
            },
            self::HEARING_DATE => match ($petitionType) {
                PetitionVariant::BEZWAAR => [
                    self::FINAL_RESULT,
                ],
                PetitionVariant::WOO_VERZOEK => [
                    self::PETITION_RECEIVED,
                ],
                default => [],
            },
            self::REFERRED_TO_DEPARTMENT, self::ANSWER_RECEIVED => [
                self::FINAL_RESULT,
            ],
            self::OPINION_OUTSIDE_TERM => match ($petitionType) {
                PetitionVariant::WOO_VERZOEK => [
                    self::FINAL_RESULT,
                ],
                default => [],
            },
            self::SENT_PARTIAL_DECISION => match ($petitionType) {
                PetitionVariant::BEZWAAR, PetitionVariant::WOO_VERZOEK => [
                    self::FINAL_RESULT,
                ],
                default => [],
            },
            self::NOTICE_OF_DEFAULT_WITHDRAWN => match ($petitionType) {
                PetitionVariant::BEZWAAR, PetitionVariant::WOO_VERZOEK => [
                    self::APPEAL_DECISION_NOT_TIMELY,
                    self::FINAL_RESULT,
                ],
                default => [],
            },
            self::APPEAL_DECISION_NOT_TIMELY => [
                self::FINAL_RESULT,
            ],
            self::FINAL_RESULT => match ($petitionType) {
                PetitionVariant::BEZWAAR => [
                    self::PETITION_RECEIVED,
                ],
                PetitionVariant::WOO_VERZOEK => [
                    self::PRIMARY_DECISION,
                    self::RECEIPT_OF_OBJECTION,
                ],
                default => [],
            },
            default => [],
        };
    }

    public function label(?PetitionVariant $petitionType = null): string
    {
        return $this->translate('label', $petitionType);
    }

    public function requiresPrecedingLastEvent(bool $isRepeat): ?self
    {
        return match ($this) {
            self::NOTICE_OF_DEFAULT_WITHDRAWN => self::NOTICE_OF_DEFAULT_RECEIVED,
            self::NOTICE_OF_DEFAULT_RECEIVED => $isRepeat ? self::NOTICE_OF_DEFAULT_WITHDRAWN : null,
            default => null,
        };
    }

    /**
     * The event this one closes. It can only be added while such an event is still open.
     */
    public function closes(): ?self
    {
        return match ($this) {
            self::APPEAL_DECISION_NOT_TIMELY => self::RECEIPT_APPEAL_NOT_TIMELY,
            self::ANSWER_RECEIVED => self::REFERRED_TO_DEPARTMENT,
            default => null,
        };
    }

    /**
     * The event that has to close this one before it can be added again.
     */
    public function mustBeClosedBy(): ?self
    {
        return match ($this) {
            self::REFERRED_TO_DEPARTMENT => self::ANSWER_RECEIVED,
            default => null,
        };
    }

    public function fitsOpenEvents(WizardEventCollection $events): bool
    {
        $opening = $this->closes();

        if ($opening instanceof self) {
            return $events->hasOpen($opening, $this);
        }

        $closing = $this->mustBeClosedBy();

        return !$closing instanceof self || !$events->hasOpen($this, $closing);
    }

    public function description(?PetitionVariant $petitionType = null): string
    {
        return $this->translate('description', $petitionType);
    }

    protected function translate(string $translationType, ?PetitionVariant $petitionType = null): string
    {
        $typeKey = $petitionType->value ?? 'default';
        $eventKey = $this->value;

        $specificKey = sprintf('petition_event.%s.%s.%s', $typeKey, $translationType, $eventKey);

        if (Lang::has($specificKey)) {
            return Lang::get($specificKey);
        }

        $defaultKey = sprintf('petition_event.default.%s.%s', $translationType, $eventKey);

        if (Lang::has($defaultKey)) {
            return Lang::get($defaultKey);
        }

        return $specificKey;
    }
}
