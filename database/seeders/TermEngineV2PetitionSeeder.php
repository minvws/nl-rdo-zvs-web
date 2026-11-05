<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Petition\PetitionCreateAction;
use App\Actions\PetitionEvent\PetitionEventsPersistenceAction;
use App\Enums\AdjournmentEndReason;
use App\Enums\HearingForm;
use App\Enums\PetitionEventType;
use App\Enums\PetitionVariant;
use App\Enums\ResultType;
use App\Enums\StatusGroup;
use App\Enums\SuspensionType;
use App\Factories\PetitionEventDataFactory;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionCategory;
use App\Models\PetitionEvent;
use App\Models\PetitionStatus;
use App\Models\PetitionStatusHistory;
use App\Models\PetitionType;
use App\Models\Team;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

use function array_keys;
use function array_map;
use function array_slice;
use function in_array;
use function max;
use function min;
use function sprintf;

/**
 * Seeds petitions that run on the term engine v2 (petition events)
 *
 * Every petition is named after the scenario it demonstrates and all dates are relative to today, so
"deadline almost over" stays almost over no matter when the database is seeded.
 *
 * Every scenario that is not closed yet automatically gets a second petition in which the scenario is closed by a
 * final decision (daarna afgerond met besluit). Add a scenario to the lists below and its closed variant follows by
 * itself, see withClosedVariants().
 *
 * @phpstan-type EventData array{type: PetitionEventType, days_ago: int, duration?: int, penalties?: list<array{amount: int, duration: int}>, suspension_type?: SuspensionType, result_type?: ResultType, hearing_form?: HearingForm, reasoning?: string, withdrawal_duration?: int}
 * @phpstan-type Scenario array{team: string, type: string, variant: PetitionVariant, name: string, description: string, title?: string, closed?: bool, events: list<EventData>}
 */
class TermEngineV2PetitionSeeder extends Seeder
{
    private const string TEAM_NAME = "Team test scenario's";

    public function __construct(
        private readonly PetitionCreateAction $petitionCreateAction,
        private readonly PetitionEventsPersistenceAction $petitionEventsPersistenceAction,
    ) {
    }

    private const array RECEIPT_EVENT_TYPES = [
        PetitionEventType::RECEIPT_OF_OBJECTION,
        PetitionEventType::PETITION_RECEIVED,
        PetitionEventType::RECEIPT_OF_COMPLAINT,
    ];

    public function run(): void
    {
        // Petitions only get events instead of terms when the term engine v2 is enabled
        Config::set('app.features.term_engine_v2', true);

        $user = User::query()->where('email', 'admin@minvws.nl')->firstOrFail();

        foreach (self::scenarios() as $scenario) {
            $this->createPetition($scenario, $user);
        }
    }

    /**
     * @return list<Scenario>
     */
    public static function scenarios(): array
    {
        $scenarios = [
            ...self::bezwaarScenarios('team-c', 'Bezwaarprocedure'),
            ...self::committeeBezwaarScenarios(),
            ...self::wooScenarios('team-a', 'WOO-verzoek'),
            ...self::wooScenarios('team-b', 'WOO-verzoek (covid-19)'),
            ...self::teamIScenarios(),
            ...self::klachtScenarios(),
        ];

        foreach (array_keys($scenarios) as $index) {
            $scenarios[$index]['title'] = sprintf('scenario-%03d', $index + 1);
        }

        return $scenarios;
    }

    /**
     * @return list<Scenario>
     */
    private static function bezwaarScenarios(string $team, string $type): array
    {
        $scenarios = [
            'Net ontvangen' => [
                self::event(PetitionEventType::PRIMARY_DECISION, 5, 42),
                self::event(PetitionEventType::RECEIPT_OF_OBJECTION, 2, 42),
            ],
            'Lopend, ruim binnen de beslistermijn' => [
                self::event(PetitionEventType::PRIMARY_DECISION, 30, 42),
                self::event(PetitionEventType::RECEIPT_OF_OBJECTION, 20, 42),
            ],
            'Beslistermijn verloopt over 3 dagen' => self::objection(71),
            'Beslistermijn verstreken, nog geen ingebrekestelling' => [
                self::event(PetitionEventType::PRIMARY_DECISION, 90, 42),
                self::event(PetitionEventType::RECEIPT_OF_OBJECTION, 80, 42),
            ],
            'Verdaagd met 6 weken' => [
                self::event(PetitionEventType::PRIMARY_DECISION, 60, 42),
                self::event(PetitionEventType::RECEIPT_OF_OBJECTION, 50, 42),
                self::event(PetitionEventType::ADJOURNMENT, 20, 42),
            ],
            'Opgeschort met brief, opschorting loopt' => [
                self::event(PetitionEventType::PRIMARY_DECISION, 50, 42),
                self::event(PetitionEventType::RECEIPT_OF_OBJECTION, 40, 42),
                self::event(PetitionEventType::LETTER_OF_SUSPENSION_SENT, 10, 28, suspensionType: SuspensionType::SUSPENSION),
            ],
            'Opschorting vroegtijdig beeindigd' => [
                self::event(PetitionEventType::PRIMARY_DECISION, 60, 42),
                self::event(PetitionEventType::RECEIPT_OF_OBJECTION, 50, 42),
                self::event(PetitionEventType::LETTER_OF_SUSPENSION_SENT, 30, 28, suspensionType: SuspensionType::SUSPENSION),
                self::event(PetitionEventType::SUSPENSION_END, 15),
            ],
            'Opschorting voor het specificeren van gronden' => [
                self::event(PetitionEventType::PRIMARY_DECISION, 40, 42),
                self::event(PetitionEventType::RECEIPT_OF_OBJECTION, 30, 42),
                self::event(PetitionEventType::LETTER_OF_SUSPENSION_SENT, 25, 14, suspensionType: SuspensionType::SPECIFICATION),
            ],
            'Opschorting voor overleg met bezwaarde' => [
                self::event(PetitionEventType::PRIMARY_DECISION, 40, 42),
                self::event(PetitionEventType::RECEIPT_OF_OBJECTION, 30, 42),
                self::event(PetitionEventType::LETTER_OF_SUSPENSION_SENT, 12, 28, suspensionType: SuspensionType::CONSULTATION),
            ],
            'Gespecificeerde aanhouding' => [
                self::event(PetitionEventType::PRIMARY_DECISION, 40, 42),
                self::event(PetitionEventType::RECEIPT_OF_OBJECTION, 30, 42),
                self::event(PetitionEventType::LETTER_OF_SUSPENSION_SENT, 10, 28, suspensionType: SuspensionType::SPECIFIED_ADJOURNMENT),
            ],
            'Ongespecificeerde aanhouding loopt nog' => [
                self::event(PetitionEventType::PRIMARY_DECISION, 50, 42),
                self::event(PetitionEventType::RECEIPT_OF_OBJECTION, 40, 42),
                self::event(
                    PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                    10,
                    withdrawalDuration: 28,
                    reasoning: 'Wachten op uitspraak in een gerelateerde zaak.',
                ),
            ],
            'Ongespecificeerde aanhouding beeindigd door gebeurtenis' => [
                self::event(PetitionEventType::PRIMARY_DECISION, 60, 42),
                self::event(PetitionEventType::RECEIPT_OF_OBJECTION, 50, 42),
                self::event(
                    PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                    30,
                    withdrawalDuration: 28,
                    reasoning: 'Wachten op uitspraak in een gerelateerde zaak.',
                ),
                self::event(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END, 5, reasoning: AdjournmentEndReason::Event->value),
            ],
            'Ongespecificeerde aanhouding ingetrokken' => [
                self::event(PetitionEventType::PRIMARY_DECISION, 60, 42),
                self::event(PetitionEventType::RECEIPT_OF_OBJECTION, 50, 42),
                self::event(
                    PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                    30,
                    withdrawalDuration: 28,
                    reasoning: 'Bezwaarde vroeg om uitstel.',
                ),
                self::event(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END, 5, reasoning: AdjournmentEndReason::Withdrawal->value),
            ],
            'Hoorzitting ingepland' => [
                self::event(PetitionEventType::PRIMARY_DECISION, 30, 42),
                self::event(PetitionEventType::RECEIPT_OF_OBJECTION, 20, 42),
                self::event(PetitionEventType::MEETING_SCHEDULED, 5, 21),
            ],
            'Hoorzitting gehouden, digitaal' => [
                self::event(PetitionEventType::PRIMARY_DECISION, 40, 42),
                self::event(PetitionEventType::RECEIPT_OF_OBJECTION, 30, 42),
                self::event(PetitionEventType::HEARING_DATE, 8, hearingForm: HearingForm::DIGITAL),
            ],
            'Ingebrekestelling ontvangen, dwangsom loopt' => [
                ...self::objection(90),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, 8, 14, penalties: self::noticeOfDefaultPenalties(3)),
            ],
            'Ingebrekestelling ontvangen, dwangsom verbeurd' => [
                ...self::objection(150),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, 65, 14, penalties: self::noticeOfDefaultPenalties(3)),
            ],
            'Ingebrekestelling ingetrokken' => [
                ...self::objection(90),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, 8, 14, penalties: self::noticeOfDefaultPenalties(1)),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN, 4),
            ],
            'Beroep niet tijdig ingesteld, dwangsom loopt' => [
                ...self::objection(190),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, 100, 14, penalties: self::noticeOfDefaultPenalties(3)),
                self::event(PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY, 40),
                self::event(PetitionEventType::APPEAL_DECISION_NOT_TIMELY, 30, 14, penalties: self::appealNotTimelyPenalties()),
            ],
            'Afgerond, gegrond binnen de termijn' => [
                self::event(PetitionEventType::PRIMARY_DECISION, 60, 42),
                self::event(PetitionEventType::RECEIPT_OF_OBJECTION, 50, 42),
                self::event(
                    PetitionEventType::FINAL_RESULT,
                    10,
                    resultType: ResultType::FINAL_DECISION,
                    reasoning: 'Bezwaar gegrond verklaard.',
                ),
            ],
            'Afgerond, bezwaar ingetrokken' => [
                self::event(PetitionEventType::PRIMARY_DECISION, 40, 42),
                self::event(PetitionEventType::RECEIPT_OF_OBJECTION, 30, 42),
                self::event(PetitionEventType::FINAL_RESULT, 12, resultType: ResultType::WITHDRAWN),
            ],
            'Afgerond, doorgezonden' => [
                ...self::objection(30),
                self::event(
                    PetitionEventType::FINAL_RESULT,
                    7,
                    resultType: ResultType::FORWARDED,
                    reasoning: 'Doorgezonden naar het bevoegde bestuursorgaan.',
                ),
            ],
            'Beroep niet tijdig ontvangen, nog zonder beslissing' => [
                ...self::objection(150),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, 60, 14, penalties: self::noticeOfDefaultPenalties(3)),
                self::event(PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY, 20),
            ],
            'Beroep niet tijdig ontvangen zonder ingebrekestelling' => [
                ...self::objection(100),
                self::event(PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY, 15),
            ],
            'Beroep niet tijdig ingesteld, dwangsom verbeurd' => [
                ...self::objection(400),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, 300, 14, penalties: self::noticeOfDefaultPenalties(3)),
                self::event(PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY, 250),
                self::event(PetitionEventType::APPEAL_DECISION_NOT_TIMELY, 240, 14, penalties: self::appealNotTimelyPenalties()),
            ],
            'Tweede ingebrekestelling na intrekking van de eerste' => [
                ...self::objection(150),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, 65, 14, penalties: self::noticeOfDefaultPenalties(3)),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN, 50),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, 30, 14, penalties: self::noticeOfDefaultPenalties(3)),
            ],
            'Opschorting beeindigd, daarna ingebrekestelling' => [
                ...self::objection(130),
                self::event(PetitionEventType::LETTER_OF_SUSPENSION_SENT, 100, 28, suspensionType: SuspensionType::SUSPENSION),
                self::event(PetitionEventType::SUSPENSION_END, 80),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, 30, 14, penalties: self::noticeOfDefaultPenalties(3)),
            ],
            'Verdaagd, daarna ingebrekestelling' => [
                ...self::objection(130),
                self::event(PetitionEventType::ADJOURNMENT, 100, 42),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, 5, 14, penalties: self::noticeOfDefaultPenalties(3)),
            ],
            'Ongespecificeerde aanhouding beeindigd, daarna ingebrekestelling' => [
                ...self::objection(130),
                self::event(
                    PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                    110,
                    withdrawalDuration: 28,
                    reasoning: 'Wachten op uitspraak in een gerelateerde zaak.',
                ),
                self::event(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END, 90, reasoning: AdjournmentEndReason::Event->value),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, 30, 14, penalties: self::noticeOfDefaultPenalties(3)),
            ],
            'Twee opschortingen na elkaar' => [
                ...self::objection(80),
                self::event(PetitionEventType::LETTER_OF_SUSPENSION_SENT, 50, 14, suspensionType: SuspensionType::SPECIFICATION),
                self::event(PetitionEventType::SUSPENSION_END, 40),
                self::event(PetitionEventType::LETTER_OF_SUSPENSION_SENT, 20, 28, suspensionType: SuspensionType::CONSULTATION),
            ],
            'Twee ongespecificeerde aanhoudingen na elkaar' => [
                ...self::objection(120),
                self::event(PetitionEventType::UNSPECIFIED_ADJOURNMENT, 90, withdrawalDuration: 28, reasoning: 'Eerste aanhouding.'),
                self::event(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END, 70, reasoning: AdjournmentEndReason::Withdrawal->value),
                self::event(PetitionEventType::UNSPECIFIED_ADJOURNMENT, 30, withdrawalDuration: 28, reasoning: 'Tweede aanhouding.'),
            ],
            'Hoorzitting telefonisch gehouden' => [
                ...self::objection(40),
                self::event(PetitionEventType::HEARING_DATE, 8, hearingForm: HearingForm::TELEPHONE),
            ],
            'Hoorzitting fysiek gehouden' => [
                ...self::objection(40),
                self::event(PetitionEventType::HEARING_DATE, 8, hearingForm: HearingForm::PHYSICAL),
            ],
            'Hoorzitting ingepland en gehouden' => [
                ...self::objection(50),
                self::event(PetitionEventType::MEETING_SCHEDULED, 30, 21),
                self::event(PetitionEventType::HEARING_DATE, 8, hearingForm: HearingForm::DIGITAL),
            ],
            'Hoorzitting gehouden, daarna verdaagd' => [
                ...self::objection(60),
                self::event(PetitionEventType::HEARING_DATE, 30, hearingForm: HearingForm::PHYSICAL),
                self::event(PetitionEventType::ADJOURNMENT, 10, 42),
            ],
        ];

        return self::toScenarios($scenarios, $team, $type, PetitionVariant::BEZWAAR, 'Bezwaar');
    }

    /**
     * WJZ-BB handles objections through a committee instead of regular hearings.
     *
     * @return list<Scenario>
     */
    private static function committeeBezwaarScenarios(): array
    {
        $scenarios = [
            'Commissiezitting vastgesteld' => [
                self::event(PetitionEventType::PRIMARY_DECISION, 40, 42),
                self::event(PetitionEventType::RECEIPT_OF_OBJECTION, 30, 42),
                self::event(PetitionEventType::COMMITTEE_HEARING_DETERMINED, 10, 42),
            ],
            'Commissiezitting gehouden' => [
                self::event(PetitionEventType::PRIMARY_DECISION, 70, 42),
                self::event(PetitionEventType::RECEIPT_OF_OBJECTION, 60, 42),
                self::event(PetitionEventType::COMMITTEE_HEARING_DETERMINED, 40, 42),
                self::event(PetitionEventType::HEARING_DATE, 10, hearingForm: HearingForm::COMMITTEE),
            ],
            'Commissiezitting en ingebrekestelling' => [
                ...self::objection(190),
                self::event(PetitionEventType::COMMITTEE_HEARING_DETERMINED, 150, 42),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, 40, 14, penalties: self::noticeOfDefaultPenalties(3)),
            ],
            'Afgerond na commissieadvies' => [
                self::event(PetitionEventType::PRIMARY_DECISION, 90, 42),
                self::event(PetitionEventType::RECEIPT_OF_OBJECTION, 80, 42),
                self::event(PetitionEventType::COMMITTEE_HEARING_DETERMINED, 55, 42),
                self::event(PetitionEventType::HEARING_DATE, 30, hearingForm: HearingForm::COMMITTEE),
                self::event(PetitionEventType::FINAL_RESULT, 5, resultType: ResultType::FINAL_DECISION),
            ],
        ];

        return self::toScenarios($scenarios, 'wjz-bb', 'Bezwaarprocedure', PetitionVariant::BEZWAAR, 'Bezwaar commissie');
    }

    /**
     * @return list<Scenario>
     */
    private static function wooScenarios(string $team, string $type): array
    {
        $penalties = [
            ['amount' => 100, 'duration' => 150],
        ];

        $scenarios = [
            'Net ontvangen' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 2, 28),
            ],
            'Lopend, ruim binnen de beslistermijn' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 10, 28),
            ],
            'Beslistermijn verloopt over 2 dagen' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 26, 28),
            ],
            'Beslistermijn verstreken, nog geen ingebrekestelling' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 60, 28),
            ],
            'Verdaagd met 2 weken' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 25, 28),
                self::event(PetitionEventType::ADJOURNMENT, 5, 14),
            ],
            'Gesprek met verzoeker ingepland' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 15, 28),
                self::event(PetitionEventType::MEETING_SCHEDULED, 3, 7),
            ],
            'Opgeschort voor precisering' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 20, 28),
                self::event(PetitionEventType::LETTER_OF_SUSPENSION_SENT, 8, 14, suspensionType: SuspensionType::SPECIFICATION),
            ],
            'Opschorting beeindigd na precisering' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 40, 28),
                self::event(PetitionEventType::LETTER_OF_SUSPENSION_SENT, 30, 14, suspensionType: SuspensionType::SPECIFICATION),
                self::event(PetitionEventType::SUSPENSION_END, 20),
            ],
            'Ongespecificeerde aanhouding loopt nog' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 20, 28),
                self::event(
                    PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                    7,
                    withdrawalDuration: 14,
                    reasoning: 'Wachten op reactie van een andere bestuursorgaan.',
                ),
            ],
            'Deelbesluit verstuurd, zaak loopt door' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 40, 28),
                self::event(PetitionEventType::SENT_PARTIAL_DECISION, 10),
            ],
            'Advies buiten de termijn' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 60, 28),
                self::event(PetitionEventType::OPINION_OUTSIDE_TERM, 20),
            ],
            'Ingebrekestelling ontvangen, dwangsom loopt' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 70, 28),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, 20, 14),
            ],
            'Ingebrekestelling ingetrokken' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 70, 28),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, 25, 14),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN, 15),
            ],
            'Beroep niet tijdig ingesteld' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 150, 28),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, 100, 14),
                self::event(PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY, 60),
                self::event(PetitionEventType::APPEAL_DECISION_NOT_TIMELY, 50, 14, penalties: $penalties),
            ],
            'Afgerond met een besluit, nog niet openbaar' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 30, 28),
                self::event(PetitionEventType::FINAL_RESULT, 5, resultType: ResultType::FINAL_DECISION),
            ],
            'Afgerond met een besluit, feitelijk verstrekt' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 40, 28),
                self::event(PetitionEventType::FINAL_RESULT, 12, resultType: ResultType::FINAL_DECISION),
                self::event(PetitionEventType::ACTUAL_DISCLOSURE, 8),
            ],
            'Afgerond met een besluit, verstrekt en gepubliceerd' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 60, 28),
                self::event(PetitionEventType::FINAL_RESULT, 25, resultType: ResultType::FINAL_DECISION),
                self::event(PetitionEventType::ACTUAL_DISCLOSURE, 15),
                self::event(PetitionEventType::PUBLICATION_DATE, 3),
            ],
            'Afgerond op artikel 5.5 verzoek' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 50, 28),
                self::event(PetitionEventType::FINAL_RESULT, 10, resultType: ResultType::FINAL_DECISION_55_REQUEST),
            ],
            'Afgerond, verzoek ingetrokken' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 20, 28),
                self::event(PetitionEventType::FINAL_RESULT, 4, resultType: ResultType::WITHDRAWN),
            ],
            'Afgerond, doorverwezen' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 20, 28),
                self::event(PetitionEventType::FINAL_RESULT, 6, resultType: ResultType::FORWARDED),
            ],
            'Afgerond, informatie was al openbaar' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 20, 28),
                self::event(PetitionEventType::FINAL_RESULT, 6, resultType: ResultType::ALREADY_PUBLIC),
            ],
            'Afgerond, afgewezen' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 35, 28),
                self::event(
                    PetitionEventType::FINAL_RESULT,
                    9,
                    resultType: ResultType::REJECTED,
                    reasoning: 'Uitzonderingsgrond artikel 5.1 Woo.',
                ),
            ],
            'Afgerond, niet-ontvankelijk' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 30, 28),
                self::event(
                    PetitionEventType::FINAL_RESULT,
                    8,
                    resultType: ResultType::DISMISSED,
                    reasoning: 'Verzoek onvoldoende gespecificeerd.',
                ),
            ],
            'Afgerond, heroverwogen' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 45, 28),
                self::event(PetitionEventType::FINAL_RESULT, 9, resultType: ResultType::RECONSIDERED),
            ],
            'Afgerond, anders' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 30, 28),
                self::event(
                    PetitionEventType::FINAL_RESULT,
                    6,
                    resultType: ResultType::OTHER,
                    reasoning: 'Verzoek is samengevoegd met een ander verzoek.',
                ),
            ],
            'Afgerond op artikel 5.5 verzoek, feitelijk verstrekt' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 50, 28),
                self::event(PetitionEventType::FINAL_RESULT, 15, resultType: ResultType::FINAL_DECISION_55_REQUEST),
                self::event(PetitionEventType::ACTUAL_DISCLOSURE, 5),
            ],
            'Afgewezen en gepubliceerd' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 60, 28),
                self::event(
                    PetitionEventType::FINAL_RESULT,
                    20,
                    resultType: ResultType::REJECTED,
                    reasoning: 'Uitzonderingsgrond artikel 5.1 Woo.',
                ),
                self::event(PetitionEventType::PUBLICATION_DATE, 4),
            ],
            'Opgeschort voor consultatie' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 20, 28),
                self::event(PetitionEventType::LETTER_OF_SUSPENSION_SENT, 6, 14, suspensionType: SuspensionType::CONSULTATION),
            ],
            'Opgeschort, brief verzonden' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 20, 28),
                self::event(PetitionEventType::LETTER_OF_SUSPENSION_SENT, 6, 14, suspensionType: SuspensionType::SUSPENSION),
            ],
            'Gespecificeerde aanhouding' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 20, 28),
                self::event(PetitionEventType::LETTER_OF_SUSPENSION_SENT, 6, 14, suspensionType: SuspensionType::SPECIFIED_ADJOURNMENT),
            ],
            'Ongespecificeerde aanhouding beeindigd door gebeurtenis' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 50, 28),
                self::event(
                    PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                    30,
                    withdrawalDuration: 14,
                    reasoning: 'Wachten op reactie van een ander bestuursorgaan.',
                ),
                self::event(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END, 10, reasoning: AdjournmentEndReason::Event->value),
            ],
            'Ongespecificeerde aanhouding ingetrokken' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 50, 28),
                self::event(
                    PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                    30,
                    withdrawalDuration: 14,
                    reasoning: 'Verzoeker vroeg om uitstel.',
                ),
                self::event(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END, 10, reasoning: AdjournmentEndReason::Withdrawal->value),
            ],
            'Verdaagd, daarna ingebrekestelling' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 100, 28),
                self::event(PetitionEventType::ADJOURNMENT, 80, 14),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, 30, 14),
            ],
            'Opschorting beeindigd, daarna ingebrekestelling' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 100, 28),
                self::event(PetitionEventType::LETTER_OF_SUSPENSION_SENT, 80, 14, suspensionType: SuspensionType::SPECIFICATION),
                self::event(PetitionEventType::SUSPENSION_END, 65),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, 20, 14),
            ],
            'Advies buiten de termijn, daarna ingebrekestelling' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 90, 28),
                self::event(PetitionEventType::OPINION_OUTSIDE_TERM, 50),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, 20, 14),
            ],
            'Tweede ingebrekestelling na intrekking van de eerste' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 120, 28),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, 80, 14),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN, 60),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, 20, 14),
            ],
            'Beroep niet tijdig ontvangen, nog zonder beslissing' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 100, 28),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, 65, 14),
                self::event(PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY, 20),
            ],
            'Beroep niet tijdig ingesteld, dwangsom verbeurd' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 400, 28),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, 350, 14),
                self::event(PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY, 300),
                self::event(PetitionEventType::APPEAL_DECISION_NOT_TIMELY, 290, 14, penalties: self::appealNotTimelyPenalties()),
            ],
            'Twee deelbesluiten, zaak loopt door' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 70, 28),
                self::event(PetitionEventType::SENT_PARTIAL_DECISION, 30),
                self::event(PetitionEventType::SENT_PARTIAL_DECISION, 10),
            ],
            'Deelbesluit verstuurd en feitelijk verstrekt' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 50, 28),
                self::event(PetitionEventType::SENT_PARTIAL_DECISION, 20),
                self::event(PetitionEventType::ACTUAL_DISCLOSURE, 5),
            ],
            'Deelbesluit, verstrekt, afgerond en gepubliceerd' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 80, 28),
                self::event(PetitionEventType::SENT_PARTIAL_DECISION, 50),
                self::event(PetitionEventType::ACTUAL_DISCLOSURE, 30),
                self::event(PetitionEventType::FINAL_RESULT, 10, resultType: ResultType::FINAL_DECISION),
                self::event(PetitionEventType::PUBLICATION_DATE, 3),
            ],
        ];

        return self::toScenarios($scenarios, $team, $type, PetitionVariant::WOO_VERZOEK, 'Woo');
    }

    /**
     * Team I forwards requests to other departments, which is what the referral events are for.
     *
     * @return list<Scenario>
     */
    private static function teamIScenarios(): array
    {
        $scenarios = [
            'Net ontvangen' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 2, 28),
            ],
            'Doorverwezen naar een afdeling, wacht op antwoord' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 20, 28),
                self::event(PetitionEventType::REFERRED_TO_DEPARTMENT, 8),
            ],
            'Doorverwezen, beslistermijn verstreken zonder antwoord' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 60, 28),
                self::event(PetitionEventType::REFERRED_TO_DEPARTMENT, 40),
            ],
            'Doorverwezen en antwoord ontvangen' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 30, 28),
                self::event(PetitionEventType::REFERRED_TO_DEPARTMENT, 20),
                self::event(PetitionEventType::ANSWER_RECEIVED, 10),
            ],
            'Twee keer doorverwezen, tweede antwoord nog open' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 40, 28),
                self::event(PetitionEventType::REFERRED_TO_DEPARTMENT, 30),
                self::event(PetitionEventType::ANSWER_RECEIVED, 20),
                self::event(PetitionEventType::REFERRED_TO_DEPARTMENT, 10),
            ],
            'Doorverwezen en ongespecificeerde aanhouding' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 40, 28),
                self::event(PetitionEventType::REFERRED_TO_DEPARTMENT, 30),
                self::event(
                    PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                    10,
                    withdrawalDuration: 14,
                    reasoning: 'Wachten op antwoord van de afdeling.',
                ),
            ],
            'Doorverwezen, antwoord ontvangen en ingebrekestelling' => [
                self::event(PetitionEventType::PETITION_RECEIVED, 100, 28),
                self::event(PetitionEventType::REFERRED_TO_DEPARTMENT, 80),
                self::event(PetitionEventType::ANSWER_RECEIVED, 60),
                self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, 20, 14),
            ],
        ];

        return self::toScenarios($scenarios, 'team-i', 'Informatieverzoek', PetitionVariant::WOO_VERZOEK, 'Woo');
    }

    /**
     * @return list<Scenario>
     */
    private static function klachtScenarios(): array
    {
        $scenarios = [
            'Net ontvangen' => [
                self::event(PetitionEventType::RECEIPT_OF_COMPLAINT, 2, 42),
            ],
            'Lopend, ruim binnen de termijn' => [
                self::event(PetitionEventType::RECEIPT_OF_COMPLAINT, 14, 42),
            ],
            'Termijn verloopt over 4 dagen' => [
                self::event(PetitionEventType::RECEIPT_OF_COMPLAINT, 38, 42),
            ],
            'Termijn verstreken' => [
                self::event(PetitionEventType::RECEIPT_OF_COMPLAINT, 70, 42),
            ],
            'Verdaagd met 4 weken' => [
                self::event(PetitionEventType::RECEIPT_OF_COMPLAINT, 35, 42),
                self::event(PetitionEventType::ADJOURNMENT, 10, 28),
            ],
            'Afgerond binnen de termijn' => [
                self::event(PetitionEventType::RECEIPT_OF_COMPLAINT, 30, 42),
                self::event(PetitionEventType::FINAL_RESULT, 6, resultType: ResultType::FINAL_DECISION),
            ],
            'Afgerond buiten de termijn' => [
                self::event(PetitionEventType::RECEIPT_OF_COMPLAINT, 80, 42),
                self::event(PetitionEventType::FINAL_RESULT, 10, resultType: ResultType::FINAL_DECISION),
            ],
            'Afgerond, klacht ingetrokken' => [
                self::event(PetitionEventType::RECEIPT_OF_COMPLAINT, 25, 42),
                self::event(PetitionEventType::FINAL_RESULT, 8, resultType: ResultType::WITHDRAWN),
            ],
            'Afgerond, anders' => [
                self::event(PetitionEventType::RECEIPT_OF_COMPLAINT, 25, 42),
                self::event(
                    PetitionEventType::FINAL_RESULT,
                    8,
                    resultType: ResultType::OTHER,
                    reasoning: 'Klacht is niet in behandeling genomen.',
                ),
            ],
        ];

        return self::toScenarios($scenarios, 'team-wjz-klachten', 'Klacht', PetitionVariant::KLACHT, 'Klacht');
    }

    /**
     * The initial events of an objection: the primary decision and the receipt of the objection.
     *
     * @return list<EventData>
     */
    private static function objection(int $receivedDaysAgo): array
    {
        return [
            self::event(PetitionEventType::PRIMARY_DECISION, $receivedDaysAgo + 10, 42),
            self::event(PetitionEventType::RECEIPT_OF_OBJECTION, $receivedDaysAgo, 42),
        ];
    }

    /**
     * @return list<array{amount: int, duration: int}>
     */
    private static function noticeOfDefaultPenalties(int $count): array
    {
        return array_slice([
            ['amount' => 23, 'duration' => 14],
            ['amount' => 35, 'duration' => 14],
            ['amount' => 45, 'duration' => 14],
        ], 0, $count);
    }

    /**
     * @return list<array{amount: int, duration: int}>
     */
    private static function appealNotTimelyPenalties(): array
    {
        return [['amount' => 100, 'duration' => 150]];
    }

    /**
     * Every open scenario also exists in a variant that is closed with a decision afterwards, so each state
     * (for example a penalty that has been forfeited) is seen both before and after the final result.
     *
     * @param array<string, list<EventData>> $scenarios
     *
     * @return array<string, list<EventData>>
     */
    private static function withClosedVariants(array $scenarios): array
    {
        $result = $scenarios;

        foreach ($scenarios as $name => $events) {
            if (self::hasFinalResult($events)) {
                continue;
            }

            $latest = min(array_map(static fn (array $event): int => $event['days_ago'], $events));
            $result[$name . ', daarna afgerond met besluit'] = [
                ...$events,
                self::event(PetitionEventType::FINAL_RESULT, max(0, $latest - 2), resultType: ResultType::FINAL_DECISION),
            ];
        }

        return $result;
    }

    /**
     * @param array<string, list<EventData>> $scenarios
     *
     * @return list<Scenario>
     */
    private static function toScenarios(array $scenarios, string $team, string $type, PetitionVariant $variant, string $label): array
    {
        $result = [];

        foreach (self::withClosedVariants($scenarios) as $name => $events) {
            $result[] = [
                'team' => $team,
                'type' => $type,
                'variant' => $variant,
                'name' => sprintf('TEv2 %s (%s) - %s', $label, $type, $name),
                'description' => $name,
                'closed' => self::hasFinalResult($events),
                'events' => $events,
            ];
        }

        return $result;
    }

    /**
     * @param list<EventData> $events
     */
    private static function hasFinalResult(array $events): bool
    {
        foreach ($events as $event) {
            if ($event['type'] === PetitionEventType::FINAL_RESULT) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array{amount: int, duration: int}> $penalties
     *
     * @return EventData
     */
    private static function event(
        PetitionEventType $type,
        int $daysAgo,
        ?int $duration = null,
        array $penalties = [],
        ?SuspensionType $suspensionType = null,
        ?ResultType $resultType = null,
        ?HearingForm $hearingForm = null,
        ?string $reasoning = null,
        ?int $withdrawalDuration = null,
    ): array {
        $event = ['type' => $type, 'days_ago' => $daysAgo];

        foreach (['duration' => $duration, 'withdrawal_duration' => $withdrawalDuration, 'reasoning' => $reasoning] as $key => $value) {
            if ($value !== null) {
                $event[$key] = $value;
            }
        }

        if ($penalties !== []) {
            $event['penalties'] = $penalties;
        }

        if ($suspensionType instanceof SuspensionType) {
            $event['suspension_type'] = $suspensionType;
        }

        if ($resultType instanceof ResultType) {
            $event['result_type'] = $resultType;
        }

        if ($hearingForm instanceof HearingForm) {
            $event['hearing_form'] = $hearingForm;
        }

        return $event;
    }

    /**
     * @param Scenario $scenario
     */
    private function createPetition(array $scenario, User $user): void
    {
        $department = Department::query()->where('slug', $scenario['team'])->firstOrFail();
        $petitionType = PetitionType::query()
            ->where('department_id', $department->id)
            ->where('type', $scenario['variant']->value)
            ->where('name', $scenario['type'])
            ->firstOrFail();

        $today = CarbonImmutable::now(Config::string('app.display_timezone'))->startOfDay();
        $eventDates = array_map(
            static fn (array $event): string => $today->subDays($event['days_ago'])->toDateString(),
            $scenario['events'],
        );

        $petition = $this->petitionCreateAction->execute($department, $user, $petitionType, [
            'petition_category_id' => PetitionCategory::query()->where('department_id', $department->id)->first()?->id,
            'team_id' => $this->scenarioTeam($department)->id,
            'name' => $scenario['title'] ?? $scenario['name'],
            'description' => $scenario['description'],
            'date_of_entry' => $this->dateOfEvent($scenario, $eventDates, self::RECEIPT_EVENT_TYPES),
            'date_appealed_decision' => $this->dateOfEvent($scenario, $eventDates, [PetitionEventType::PRIMARY_DECISION]),
        ]);

        $this->addFollowUpEvents($petition, $scenario, $eventDates, $user);

        if ($scenario['closed'] ?? false) {
            $this->closePetition($petition, $this->statusFor($petitionType, StatusGroup::CLOSED));
        }
    }

    /**
     * @param Scenario $scenario
     * @param list<string> $eventDates
     * @param list<PetitionEventType> $types
     */
    private function dateOfEvent(array $scenario, array $eventDates, array $types): ?string
    {
        foreach ($scenario['events'] as $index => $event) {
            if (in_array($event['type'], $types, true)) {
                return $eventDates[$index];
            }
        }

        return null;
    }

    /**
     * Creating a petition registers the initial events of its type, just like in the application. Every other
     * event of the scenario is a follow-up and is added as a separate action. Each action stores the complete
     * list of events of the petition.
     *
     * @param Scenario $scenario
     * @param list<string> $eventDates
     */
    private function addFollowUpEvents(Petition $petition, array $scenario, array $eventDates, User $user): void
    {
        $petitionEvents = $petition->petitionEvents
            ->map(static fn (PetitionEvent $event): array => PetitionEventDataFactory::fromModel($event)->toArray())
            ->all();
        $initialTypes = $petition->petitionEvents->map(static fn (PetitionEvent $event): PetitionEventType => $event->type);

        foreach ($scenario['events'] as $index => $event) {
            if ($initialTypes->contains($event['type'])) {
                continue;
            }

            $petitionEvents[] = [
                'type' => $event['type']->value,
                'date' => $eventDates[$index],
                'duration' => $event['duration'] ?? null,
                'penalties' => $event['penalties'] ?? [],
                'suspension_type' => ($event['suspension_type'] ?? null)?->value,
                'result_type' => ($event['result_type'] ?? null)?->value,
                'hearing_form' => ($event['hearing_form'] ?? null)?->value,
                'reasoning' => $event['reasoning'] ?? null,
                'withdrawal_duration' => $event['withdrawal_duration'] ?? null,
                'created_at' => CarbonImmutable::now(),
            ];

            $this->petitionEventsPersistenceAction->execute($petition, $petitionEvents, $user);
        }
    }

    private function scenarioTeam(Department $department): Team
    {
        return Team::query()->firstOrCreate(
            ['department_id' => $department->id, 'name' => self::TEAM_NAME],
            ['active' => true],
        );
    }

    /**
     * Not every petition type has a status in every group, so fall back to the first or last status of the type.
     */
    private function statusFor(PetitionType $petitionType, StatusGroup $group): PetitionStatus
    {
        $statuses = PetitionStatus::query()->where('petition_type_id', $petitionType->id);
        $inGroup = (clone $statuses)->where('status_group', $group->value)->orderBy('order')->first();

        if ($inGroup instanceof PetitionStatus) {
            return $inGroup;
        }

        return $statuses->orderBy('order', $group === StatusGroup::CLOSED ? 'desc' : 'asc')->firstOrFail();
    }

    private function closePetition(Petition $petition, PetitionStatus $status): void
    {
        $petition->update(['petition_status_id' => $status->id]);

        PetitionStatusHistory::query()->create([
            'petition_id' => $petition->id,
            'petition_status_id' => $status->id,
            'date' => CarbonImmutable::now(Config::string('app.display_timezone'))->toDateString(),
        ]);
    }
}
