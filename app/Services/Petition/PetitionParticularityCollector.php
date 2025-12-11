<?php

declare(strict_types=1);

namespace App\Services\Petition;

use App\Enums\TermType;
use App\Models\Petition;
use App\Models\PetitionTerm;
use App\ValueObjects\CalendarDate;
use Webmozart\Assert\Assert;

use function array_merge;
use function in_array;

class PetitionParticularityCollector
{
    private const string LABEL_NOTICE_OF_DEFAULT = 'IGS';
    private const string LABEL_SUSPENSION = 'Opsch';
    private const string LABEL_ADJOURNMENT = 'Aanh';

    /**
     * @return array<string>
     */
    public function collectParticularities(Petition $petition): array
    {
        $labels = [];

        if ($petition->petitionTerms->hasNoticeOfDefault()) {
            $labels[] = self::LABEL_NOTICE_OF_DEFAULT;
        }

        if (
            $petition->petitionTerms->filter(static function (PetitionTerm $item): bool {
                return $item->type === TermType::SUSPENSION;
            })->currentTerms(CalendarDate::today())->isNotEmpty()
        ) {
            $labels[] = self::LABEL_SUSPENSION;
        }

        if (
            $petition->petitionTerms->filter(static function (PetitionTerm $item): bool {
                return
                    in_array(
                        $item->type,
                        [
                            TermType::SPECIFIED_ADJOURNMENT,
                            TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_EVENT,
                            TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_WITHDRAWAL,
                        ],
                        true,
                    );
            })->currentTerms(CalendarDate::today())->isNotEmpty()
        ) {
            $labels[] = self::LABEL_ADJOURNMENT;
        }

        if ($petition->draftTerm?->start_date > CalendarDate::today()) {
            $labels[] = self::LABEL_ADJOURNMENT;
        }

        return array_merge($this->getLabelsFromRelatedPetitions($petition), $labels);
    }

    /**
     * @return array<array-key, string>
     */
    private function getLabelsFromRelatedPetitions(Petition $petition): array
    {
        $relatedPetitions = $petition->relatedPetitions;

        $labels = $relatedPetitions
            ->pluck('petitionType.particularity_label')
            ->filter()
            ->unique()
            ->toArray();

        Assert::allString($labels);

        return $labels;
    }
}
