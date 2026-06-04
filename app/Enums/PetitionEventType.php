<?php

declare(strict_types=1);

namespace App\Enums;

use App\Services\EventValidatorInterface;
use Illuminate\Support\Facades\Lang;

use function app;
use function resolve;
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

    public function rule(): ?EventValidatorInterface
    {
        $key = 'validation.rule.' . $this->value;

        // @codeCoverageIgnoreStart
        if (!app()->has($key)) {
            return null;
        }
        // @codeCoverageIgnoreEnd

        return resolve($key);
    }

    public function hasDuration(): bool
    {
        return match ($this) {
            self::PRIMARY_DECISION,
            self::RECEIPT_OF_OBJECTION,
            self::LETTER_OF_SUSPENSION_SENT,
            self::MEETING_SCHEDULED,
            self::ADJOURNMENT,
            self::NOTICE_OF_DEFAULT_RECEIVED,
            self::APPEAL_DECISION_NOT_TIMELY,
            self::UNSPECIFIED_ADJOURNMENT,
            self::PETITION_RECEIVED, => true,
            default => false,
        };
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

    public function hasAdjournmentEndReason(): bool
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
            self::HEARING_DATE => $petitionType === PetitionVariant::BEZWAAR,

            self::PETITION_RECEIVED, self::PUBLICATION_DATE, self::ACTUAL_DISCLOSURE,
            self::OPINION_OUTSIDE_TERM,
            self::SENT_PARTIAL_DECISION => $petitionType === PetitionVariant::WOO_VERZOEK,

            self::NOTICE_OF_DEFAULT_WITHDRAWN => match ($petitionType) {
                PetitionVariant::BEZWAAR, PetitionVariant::WOO_VERZOEK => true,
                default => false,
            },

            self::MEETING_SCHEDULED,
            self::LETTER_OF_SUSPENSION_SENT,
            self::SUSPENSION_END,
            self::NOTICE_OF_DEFAULT_RECEIVED,
            self::APPEAL_DECISION_NOT_TIMELY,
            self::RECEIPT_APPEAL_NOT_TIMELY,
            self::FINAL_RESULT,
            self::ADJOURNMENT,
            self::UNSPECIFIED_ADJOURNMENT,
            self::UNSPECIFIED_ADJOURNMENT_END => true, // Available for both types
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
            self::ACTUAL_DISCLOSURE => [self::FINAL_RESULT],

            self::PUBLICATION_DATE => [self::ACTUAL_DISCLOSURE],

            self::RECEIPT_OF_OBJECTION => [self::PRIMARY_DECISION],

            self::LETTER_OF_SUSPENSION_SENT,
            self::NOTICE_OF_DEFAULT_RECEIVED,
            self::UNSPECIFIED_ADJOURNMENT,
            self::ADJOURNMENT,
            self::MEETING_SCHEDULED,
            self::OPINION_OUTSIDE_TERM,
            self::SENT_PARTIAL_DECISION,
            self::FINAL_RESULT => match ($petitionType) {
                PetitionVariant::BEZWAAR => [self::RECEIPT_OF_OBJECTION],
                PetitionVariant::WOO_VERZOEK => [self::PETITION_RECEIVED],
                default => [],
            },

            self::RECEIPT_APPEAL_NOT_TIMELY => [self::NOTICE_OF_DEFAULT_RECEIVED],

            self::SUSPENSION_END => [self::LETTER_OF_SUSPENSION_SENT],

            self::UNSPECIFIED_ADJOURNMENT_END => [self::UNSPECIFIED_ADJOURNMENT],

            self::HEARING_DATE => match ($petitionType) {
                PetitionVariant::BEZWAAR => [self::RECEIPT_OF_OBJECTION],
                default => [],
            },
            self::NOTICE_OF_DEFAULT_WITHDRAWN => [self::NOTICE_OF_DEFAULT_RECEIVED],

            self::APPEAL_DECISION_NOT_TIMELY => [self::RECEIPT_APPEAL_NOT_TIMELY],

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
                default => [],
            },
            self::ADJOURNMENT => match ($petitionType) {
                PetitionVariant::BEZWAAR, PetitionVariant::WOO_VERZOEK => [
                    self::ADJOURNMENT,
                    self::NOTICE_OF_DEFAULT_RECEIVED,
                    self::APPEAL_DECISION_NOT_TIMELY,
                    self::FINAL_RESULT,
                ],
                default => [],
            },
            self::UNSPECIFIED_ADJOURNMENT => match ($petitionType) {
                PetitionVariant::BEZWAAR, PetitionVariant::WOO_VERZOEK => [
                    self::UNSPECIFIED_ADJOURNMENT,
                    self::NOTICE_OF_DEFAULT_RECEIVED,
                    self::APPEAL_DECISION_NOT_TIMELY,
                    self::FINAL_RESULT,
                ],
                default => [],
            },
            self::UNSPECIFIED_ADJOURNMENT_END => match ($petitionType) {
                PetitionVariant::BEZWAAR, PetitionVariant::WOO_VERZOEK => [
                    self::UNSPECIFIED_ADJOURNMENT_END,
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
            self::NOTICE_OF_DEFAULT_WITHDRAWN => match ($petitionType) {
                PetitionVariant::BEZWAAR, PetitionVariant::WOO_VERZOEK => [
                    self::APPEAL_DECISION_NOT_TIMELY,
                    self::FINAL_RESULT,
                ],
                default => [],
            },
            self::APPEAL_DECISION_NOT_TIMELY => [
                self::APPEAL_DECISION_NOT_TIMELY,
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
