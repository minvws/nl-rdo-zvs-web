<?php

declare(strict_types=1);

namespace App\Console\Commands\Petition;

use App\Enums\CustomDateLabel;
use App\Enums\PetitionEventType;
use App\Enums\PetitionVariant;
use App\Enums\ResultType;
use App\Enums\SuspensionType;
use App\Enums\TermType;
use App\Models\Petition;
use App\Models\PetitionEvent;
use App\Models\PetitionTerm;
use App\ValueObjects\CalendarDate;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Throwable;

use function in_array;
use function sprintf;

/**
 * @SuppressWarnings(PHPMD)
 */
#[Signature('petition:migrate-terms {zaaknummer} {--commit} {--overwrite}')]
#[Description('Migrate petition terms to petition events')]
class MigratePetitionTermsCommand extends Command
{
    private const array BLOCKING_TERM_TYPES = [
        TermType::APPEAL_NOT_TIMELY,
        TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_EVENT,
        TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_WITHDRAWAL,
        TermType::PENDING_TERM_AFTER_EVENT,
        TermType::PENDING_TERM_AFTER_WITHDRAWAL,
        TermType::PENALTY,
    ];

    public function __construct(
        private readonly DatabaseManager $databaseManager,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $zaaknummer = $this->argument('zaaknummer');

        if (!$zaaknummer) {
            $this->error('U dient een zaaknummer op te geven');

            return self::FAILURE;
        }

        $petition = Petition::query()
            ->where('number', $zaaknummer)
            ->first();

        if (!$petition instanceof Petition) {
            $this->error(sprintf('Petitie met zaaknummer "%s" niet gevonden', $zaaknummer));

            return self::FAILURE;
        }

        if ($petition->petitionType->type === PetitionVariant::BEROEP) {
            $this->error(sprintf('Deze zaak %s is van het type beroep en heeft geen termijnen', $zaaknummer));

            return self::FAILURE;
        }

        $petition->load(['petitionTerms', 'petitionEvents', 'customDates']);

        $blockingTermType = $this->findBlockingTermType($petition);
        if ($blockingTermType instanceof TermType) {
            $this->error($this->getBlockingTermErrorMessage($blockingTermType));

            return self::FAILURE;
        }

        $existingEventsCount = $petition->petitionEvents->count();
        if ($existingEventsCount > 0 && !$this->option('overwrite')) {
            $this->error(
                sprintf(
                    'Petitie heeft al %d events. Gebruik --overwrite om deze opnieuw te schrijven.',
                    $existingEventsCount,
                ),
            );

            return self::FAILURE;
        }

        try {
            $this->databaseManager->beginTransaction();

            if ($existingEventsCount > 0 && $this->option('overwrite')) {
                PetitionEvent::query()
                    ->where('petition_id', $petition->id)
                    ->delete();
            }

            $this->migrateTermsToEvents($petition);
            $this->migrateCustomDatesToEvents($petition);

            if ($this->option('commit')) {
                $this->databaseManager->commit();
                $this->info('Petition terms succesvol gemigreerd naar events.');
            } else {
                $this->databaseManager->rollBack();
                $this->info('Dry-run voltooid. Geen wijzigingen opgeslagen.');
            }

            return self::SUCCESS;
            // @codeCoverageIgnoreStart
        } catch (Throwable $exception) {
            $this->databaseManager->rollBack();
            $this->error('Fout tijdens migratie: ' . $exception->getMessage());

            return self::FAILURE;
            // @codeCoverageIgnoreEnd
        }
    }

    private function findBlockingTermType(Petition $petition): ?TermType
    {
        foreach ($petition->petitionTerms as $term) {
            if (in_array($term->type, self::BLOCKING_TERM_TYPES, true)) {
                return $term->type;
            }
        }

        return null;
    }

    private function getBlockingTermErrorMessage(TermType $termType): string
    {
        return match ($termType) {
            TermType::APPEAL_NOT_TIMELY => 'Petitie heeft beroep niet tijdig',
            TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_EVENT => 'Petitie heeft unspecified_adjournment_until_event',
            TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_WITHDRAWAL => 'Petitie heeft unspecified_adjournment_until_withdrawal',
            TermType::PENDING_TERM_AFTER_EVENT => 'Petitie heeft pending_term_after_event',
            TermType::PENDING_TERM_AFTER_WITHDRAWAL => 'Petitie heeft pending_term_after_withdrawal',
            TermType::PENALTY => 'Petitie heeft penalty',
            default => sprintf('Petitie heeft %s', $termType->value),
        };
    }

    private function migrateTermsToEvents(Petition $petition): void
    {
        $petitionVariant = $petition->petitionType->type;

        foreach ($petition->petitionTerms as $term) {
            $mapping = $this->getTermMapping($term->type, $petitionVariant);

            if ($mapping === null) {
                $this->warn(sprintf('Geen event type gevonden voor term type "%s"', $term->type->value));
                continue;
            }

            $eventDate = $this->calculateEventDate($term, $petition, $petitionVariant);

            PetitionEvent::query()->create([
                'petition_id' => $petition->id,
                'type' => $mapping['event_type'],
                'date' => $eventDate,
                'duration' => $term->duration_in_days,
                'suspension_type' => $mapping['suspension_type'] ?? null,
            ]);
        }
    }

    private function migrateCustomDatesToEvents(Petition $petition): void
    {
        $petitionVariant = $petition->petitionType->type;

        foreach ($petition->customDates as $customDate) {
            if ($customDate->date === null) {
                continue;
            }

            $mapping = $this->getCustomDateMapping($customDate->date_label, $petitionVariant);

            if ($mapping === null) {
                $this->warn(
                    sprintf(
                        'Geen event mapping gevonden voor custom date label "%s"',
                        $customDate->date_label->value,
                    ),
                );
                continue;
            }

            PetitionEvent::query()->create([
                'petition_id' => $petition->id,
                'type' => $mapping['event_type'],
                'date' => $customDate->date,
                'result_type' => $mapping['result_type'] ?? null,
            ]);
        }
    }

    private function calculateEventDate(
        PetitionTerm $term,
        Petition $petition,
        PetitionVariant $petitionVariant,
    ): CalendarDate {
        if ($petitionVariant === PetitionVariant::BEZWAAR && $term->type === TermType::FIRST) {
            return $petition->date_of_entry;
        }

        return $term->start_date->subDay();
    }

    /**
     * @return array{event_type: PetitionEventType, suspension_type?: SuspensionType}|null
     */
    private function getTermMapping(TermType $termType, PetitionVariant $petitionVariant): ?array
    {
        if ($petitionVariant === PetitionVariant::WOO_VERZOEK) {
            return match ($termType) {
                TermType::FIRST => ['event_type' => PetitionEventType::PETITION_RECEIVED],
                TermType::SECOND => ['event_type' => PetitionEventType::ADJOURNMENT],
                TermType::THIRD => ['event_type' => PetitionEventType::MEETING_SCHEDULED],
                TermType::SUSPENSION => [
                    'event_type' => PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                    'suspension_type' => SuspensionType::SPECIFICATION,
                ],
                TermType::NOTICE_OF_DEFAULT => ['event_type' => PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED],
                default => null,
            };
        }

        if ($petitionVariant === PetitionVariant::BEZWAAR) {
            return match ($termType) {
                TermType::FIRST => ['event_type' => PetitionEventType::RECEIPT_OF_OBJECTION],
                TermType::SECOND => ['event_type' => PetitionEventType::ADJOURNMENT],
                TermType::THIRD, TermType::COMMITTEE_HEARING => ['event_type' => PetitionEventType::MEETING_SCHEDULED],
                TermType::OBJECTION_PERIOD => ['event_type' => PetitionEventType::PRIMARY_DECISION],
                TermType::SUSPENSION => [
                    'event_type' => PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                    'suspension_type' => SuspensionType::SUSPENSION,
                ],
                TermType::SPECIFIED_ADJOURNMENT => [
                    'event_type' => PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                    'suspension_type' => SuspensionType::SPECIFIED_ADJOURNMENT,
                ],
                default => null,
            };
        }

        // @codeCoverageIgnoreStart
        return null;
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return array{event_type: PetitionEventType, result_type?: ResultType}|null
     */
    private function getCustomDateMapping(
        CustomDateLabel $customDateLabel,
        PetitionVariant $petitionVariant,
    ): ?array {
        if ($petitionVariant === PetitionVariant::BEZWAAR) {
            return match ($customDateLabel) {
                CustomDateLabel::DATE_DECISION_ON_APPEAL => [
                    'event_type' => PetitionEventType::FINAL_RESULT,
                    'result_type' => ResultType::FINAL_DECISION,
                ],
                CustomDateLabel::DATE_PUBLIC_HEARING => ['event_type' => PetitionEventType::HEARING_DATE],
                CustomDateLabel::DATE_WITHDRAWN => [
                    'event_type' => PetitionEventType::FINAL_RESULT,
                    'result_type' => ResultType::WITHDRAWN,
                ],
                default => null,
            };
        }

        if ($petitionVariant === PetitionVariant::WOO_VERZOEK) {
            return match ($customDateLabel) {
                CustomDateLabel::DATE_OF_LAST_DECISION => [
                    'event_type' => PetitionEventType::FINAL_RESULT,
                    'result_type' => ResultType::FINAL_DECISION,
                ],
                CustomDateLabel::DATE_SETTLEMENT_WITHOUT_DECISION => null,
                default => null,
            };
        }

        // @codeCoverageIgnoreStart
        return null;
        // @codeCoverageIgnoreEnd
    }
}
