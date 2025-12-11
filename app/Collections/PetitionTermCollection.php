<?php

declare(strict_types=1);

namespace App\Collections;

use App\Enums\TermType;
use App\Models\PetitionTerm;
use App\ValueObjects\CalendarDate;
use Illuminate\Database\Eloquent\Collection;
use Webmozart\Assert\Assert;

/**
 * @extends Collection<int, PetitionTerm>
 * @SuppressWarnings(PHPMD)
 */
class PetitionTermCollection extends Collection
{
    public function penalties(): PetitionTermCollection
    {
        return $this->filter(static function (PetitionTerm $item): bool {
            return $item->type === TermType::PENALTY;
        });
    }

    public function suspendables(): PetitionTermCollection
    {
        return $this->filter(static function (PetitionTerm $item) {
            return $item->type->isSuspendable();
        });
    }

    public function getLegalDateApplicableTerm(): ?PetitionTerm
    {
        return $this->suspendables()->getThirdTerm()
            ?? $this->suspendables()->getSecondTerm()
            ?? $this->suspendables()->getCommitteeHearingTerm()
            ?? $this->suspendables()->getFirstTerm();
    }

    public function isLastSuspendable(PetitionTerm $petitionTerm): bool
    {
        if (!$this->getLegalDateApplicableTerm() instanceof PetitionTerm) {
            return false;
        }

        return $this->getLegalDateApplicableTerm()->type === $petitionTerm->type;
    }

    public function suspensions(): PetitionTermCollection
    {
        return $this->filter(static function (PetitionTerm $item): bool {
            return $item->type === TermType::SUSPENSION
                || $item->type === TermType::SPECIFIED_ADJOURNMENT;
        });
    }

    public function deadline(): ?CalendarDate
    {
        $deadline = $this
            ->filter(static function (PetitionTerm $item) {
                return $item->type->isDeadlineable();
            })
            ->max('end_date');

        Assert::nullOrIsInstanceOf($deadline, CalendarDate::class);

        return $deadline;
    }

    public function totalPenalty(): int
    {
        return $this->penalties()
            ->sum(static function (PetitionTerm $item): int {
                return $item->duration_in_days * $item->penalty_amount_in_euros;
            });
    }

    public function penaltyToDate(CalendarDate $date): int
    {
        return $this
            ->penalties()
            ->filter(static function (PetitionTerm $item) use ($date): bool {
                return $item->start_date <= $date;
            })
            ->sum(static function (PetitionTerm $item) use ($date): int {
                if ($item->start_date->diffInDays($date) > $item->duration_in_days) {
                    return $item->duration_in_days * $item->penalty_amount_in_euros;
                }

                return ($item->start_date->diffInDays($date) + 1) * $item->penalty_amount_in_euros;
            });
    }

    public function hasFirstTerm(): bool
    {
        return $this->firstWhere('type', TermType::FIRST) !== null;
    }

    public function hasSecondTerm(): bool
    {
        return $this->firstWhere('type', TermType::SECOND) !== null;
    }

    public function hasThirdTerm(): bool
    {
        return $this->firstWhere('type', TermType::THIRD) !== null;
    }

    public function hasObjectionPeriod(): bool
    {
        return $this->firstWhere('type', TermType::OBJECTION_PERIOD) !== null;
    }

    public function hasCommitteeHearing(): bool
    {
        return $this->firstWhere('type', TermType::COMMITTEE_HEARING) !== null;
    }

    public function getThirdTerm(): ?PetitionTerm
    {
        return $this->firstWhere('type', TermType::THIRD);
    }

    public function getSecondTerm(): ?PetitionTerm
    {
        return $this->firstWhere('type', TermType::SECOND);
    }

    public function getFirstTerm(): ?PetitionTerm
    {
        return $this->firstWhere('type', TermType::FIRST);
    }

    public function getCommitteeHearingTerm(): ?PetitionTerm
    {
        return $this->firstWhere('type', TermType::COMMITTEE_HEARING);
    }

    public function getObjectionPeriod(): ?PetitionTerm
    {
        return $this->firstWhere('type', TermType::OBJECTION_PERIOD);
    }

    public function hasNoticeOfDefault(): bool
    {
        return $this->firstWhere('type', TermType::NOTICE_OF_DEFAULT) !== null;
    }

    public function getNoticeOfDefault(): ?PetitionTerm
    {
        return $this->firstWhere('type', TermType::NOTICE_OF_DEFAULT);
    }

    public function currentTerms(CalendarDate $date): PetitionTermCollection
    {
        return $this->filter(static function (PetitionTerm $item) use ($date): bool {
            return $item->start_date <= $date && $item->end_date >= $date;
        });
    }

    public function latestEndDate(): ?CalendarDate
    {
        if ($this->isEmpty()) {
            return null;
        }

        /** @var ?CalendarDate $latestEndDate */
        $latestEndDate = $this->max('end_date');

        return $latestEndDate;
    }

    public function totalDaysOfSuspensions(): int
    {
        return $this->suspensions()
            ->sum(static function (PetitionTerm $item): int {
                return $item->duration_in_days;
            });
    }

    public function sumOfPenaltiesPerDate(CalendarDate $date): int
    {
        return $this->currentTerms($date)
            ->penalties()
            ->sum(static function (PetitionTerm $item): int {
                return $item->penalty_amount_in_euros;
            });
    }

    public function hasPredecessor(PetitionTerm $term): bool
    {
        if ($term->type === TermType::SECOND) {
            if ($this->hasFirstTerm()) {
                return true;
            }

            return $this->hasCommitteeHearing();
        }

        if ($term->type === TermType::COMMITTEE_HEARING) {
            return $this->hasFirstTerm();
        }

        if ($term->type === TermType::THIRD) {
            return $this->hasSecondTerm();
        }

        if ($term->parent_id !== null) {
            return $this->contains(static function (PetitionTerm $item) use ($term): bool {
                return $item->id === $term->parent_id;
            });
        }

        return false;
    }

    public function getPredecessorFromType(TermType $termType): ?PetitionTerm
    {
        if ($termType === TermType::SECOND) {
            if ($this->hasCommitteeHearing()) {
                return $this->getCommitteeHearingTerm();
            }

            if ($this->hasFirstTerm()) {
                return $this->getFirstTerm();
            }

            return null;
        }

        if ($termType === TermType::COMMITTEE_HEARING) {
            if ($this->hasFirstTerm()) {
                return $this->getFirstTerm();
            }

            return null;
        }

        if ($termType === TermType::THIRD) {
            if ($this->hasSecondTerm()) {
                return $this->getSecondTerm();
            }

            return null;
        }

        return null;
    }

    public function getParentTerm(PetitionTerm $term): ?PetitionTerm
    {
        return $this->firstWhere('id', $term->parent_id);
    }

    public function getChildTerm(PetitionTerm $term): ?PetitionTerm
    {
        return $this->firstWhere('parent_id', $term->id);
    }
}
