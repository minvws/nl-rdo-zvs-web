<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Collections\PetitionCategoryCollection;
use App\Collections\PetitionStatusCollection;
use App\Collections\PetitionTypeCollection;
use App\Collections\PolicyDepartmentCollection;
use App\Collections\UserCollection;
use App\Enums\Authorization\DepartmentRole;
use App\Enums\Authorization\GlobalRole;
use App\Enums\CustomDateLabel;
use App\Enums\CustomPetitionPropertyType;
use App\Enums\PetitionVariant;
use App\Enums\StatusGroup;
use App\Enums\TermType;
use App\Models\Contact;
use App\Models\CustomPetitionProperty;
use App\Models\Department;
use App\Models\DepartmentTermTypeSetting;
use App\Models\DepartmentUser;
use App\Models\Petition;
use App\Models\PetitionCategory;
use App\Models\PetitionExport;
use App\Models\PetitionStatus;
use App\Models\PetitionStatusHistory;
use App\Models\PetitionType;
use App\Models\PetitionTypeCustomDateLabel;
use App\Models\PolicyDepartment;
use App\Models\PublicHoliday;
use App\Models\Team;
use App\Models\User;
use App\Models\UserGlobalRole;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use function collect;
use function count;
use function json_encode;
use function rand;
use function sprintf;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $departments = $this->createDepartments();

        $this->createAdminUserWithAllRoles($departments);
        $this->createPublicHolidays();
        $policyDepartments = $this->creatPolicyDepartments();

        foreach ($departments as $department) {
            $users = $this->createUsers($department);
            $petitionTypes = $this->createPetitionTypes($department);
            $petitionStatuses = $this->createPetitionStatuses($petitionTypes);
            $contacts = $this->createContacts($department);
            $this->createTeams($department);
            $this->createExports($department, $petitionTypes);
            $this->createTermTypeSettings($department);
            $this->createCustomDateLabelsPerPetitionType($petitionTypes);
            $petitionCategories = $this->createPetitionCategories($department);

            $this->createPetitions(
                $department,
                $users,
                $contacts,
                $petitionStatuses,
                $petitionTypes,
                $petitionCategories,
                $policyDepartments,
            );
        }

        $this->createWooCustomPetitionProperties();
        $this->createBeroepCustomPetitionProperties();
        $this->createBezwaarCustomPetitionProperties();
    }

    /**
     * @return Collection<int, Department>
     */
    private function createDepartments(): Collection
    {
        return Department::factory()
            ->count(4)
            ->state(new Sequence(
                ['name' => 'Team A Woo verzoeken regulier', 'slug' => 'team-a', 'config_key' => 'team-a', 'abbreviation' => 'A'],
                ['name' => 'Team B Woo verzoeken Corona', 'slug' => 'team-b', 'config_key' => 'team-b', 'abbreviation' => 'B'],
                ['name' => 'Team C Bezwaar en Beroep Woo', 'slug' => 'team-c', 'config_key' => 'team-c', 'abbreviation' => 'C'],
                ['name' => 'WJZ Afdeling Bezwaar en Beroep', 'slug' => 'wjz-bb', 'config_key' => 'wjz-bb', 'abbreviation' => 'WJZ'],
            ))
            ->create();
    }

    /**
     * @param Collection<int, Department> $departments
     */
    private function createAdminUserWithAllRoles(Collection $departments): void
    {
        /** @var User $admin */
        $admin = User::factory()
            ->fullyVerified()
            ->create([
                'name' => 'admin',
                'email' => 'admin@minvws.nl',
                'password' => Hash::make('admin'),
            ]);

        foreach (GlobalRole::cases() as $globalRole) {
            UserGlobalRole::factory()
                ->create([
                    'user_id' => $admin->id,
                    'role' => $globalRole,
                ]);
        }
        foreach (DepartmentRole::cases() as $departmentRole) {
            foreach ($departments as $department) {
                DepartmentUser::factory()
                    ->create([
                        'department_id' => $department->id,
                        'user_id' => $admin->id,
                        'role' => $departmentRole,
                    ]);
            }
        }
    }

    private function createUsers(Department $department): UserCollection
    {
        $users = new Collection();

        foreach (DepartmentRole::cases() as $departmentRole) {
            $departmentReadUserName = sprintf('%s-%s', $department->slug, Str::lower($departmentRole->value));
            $departmentReadUser = User::factory()
                ->fullyVerified()
                ->create([
                    'name' => $departmentReadUserName,
                    'email' => sprintf('%s@minvws.nl', $departmentReadUserName),
                    'password' => Hash::make('admin'),
                ]);
            $departmentReadUser->departments()->attach($department, ['role' => $departmentRole]);

            $users->push($departmentReadUser);
        }

        return new UserCollection($users);
    }

    private function createPetitionStatuses(PetitionTypeCollection $petitionTypes): PetitionStatusCollection
    {
        $petitionStatuses = new Collection();
        $order = 1;

        foreach (StatusGroup::cases() as $statusGroup) {
            foreach ($petitionTypes as $petitionType) {
                $petitionStatus = PetitionStatus::factory()
                    ->recycle($petitionType)
                    ->create([
                        'order' => $order,
                        'status_group' => $statusGroup,
                    ]);

                $petitionStatuses->push($petitionStatus);
                $order++;
            }
        }

        return new PetitionStatusCollection($petitionStatuses);
    }

    public function createPublicHolidays(): void
    {
        PublicHoliday::factory()
            ->count(11)
            ->state(new Sequence(
                ['name' => 'Nieuwjaarsdag', 'date' => '2025-01-01'],
                ['name' => 'Goede Vrijdag', 'date' => '2025-04-18'],
                ['name' => 'Eerste Paasdag', 'date' => '2025-04-20'],
                ['name' => 'Tweede Paasdag', 'date' => '2025-04-21'],
                ['name' => 'Koningsdag', 'date' => '2025-04-26'],
                ['name' => 'Bevrijdingsdag', 'date' => '2025-05-05'],
                ['name' => 'Hemelvaartsdag', 'date' => '2025-05-29'],
                ['name' => 'Pinksteren 1', 'date' => '2025-06-08'],
                ['name' => 'Pinksteren 2', 'date' => '2025-06-09'],
                ['name' => 'Eerste Kerstdag', 'date' => '2025-12-25'],
                ['name' => 'Tweede Kerstdag', 'date' => '2025-12-26'],
            ))
            ->create();
    }

    private function createWooCustomPetitionProperties(): void
    {
        $wooPetitionTypes = PetitionType::where(['type' => PetitionVariant::WOO_VERZOEK->value])->get();

        foreach ($wooPetitionTypes as $wooPetitionType) {
            CustomPetitionProperty::factory()
                ->count(20)
                ->state(new Sequence(
                    ['type' => CustomPetitionPropertyType::NAME, 'ordering' => 1, 'name' => 'Eigenschappen'],
                    ['type' => CustomPetitionPropertyType::TITLE, 'ordering' => 2, 'name' => 'Uitkomst doorgeven'],
                    ['type' => CustomPetitionPropertyType::SUBTITLE, 'ordering' => 3, 'name' => 'Zaak afdoen met besluiten'],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 4, 'name' => 'Één besluit', 'grouping' => 1],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 5, 'name' => 'Deelbesluiten', 'grouping' => 1],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 6, 'name' => 'Afwijsbesluit', 'grouping' => 1],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 7, 'name' => 'Buiten behandeling stellen', 'grouping' => 1],
                    ['type' => CustomPetitionPropertyType::SUBTITLE, 'ordering' => 8, 'name' => 'Zaak afdoen zonder besluit'],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 9, 'name' => 'Verzoek ingetrokken', 'grouping' => 1],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 10, 'name' => 'Verzoek doorverwezen', 'grouping' => 1],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 11, 'name' => 'Verzoek betrof bij nader inzien burgervraag', 'grouping' => 1],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 12, 'name' => 'Verzoek betrof reeds openbare informatie', 'grouping' => 1],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 13, 'name' => 'Anders', 'grouping' => 1],
                    ['type' => CustomPetitionPropertyType::TITLE, 'ordering' => 14, 'name' => 'Zwaarte doorgeven'],
                    ['type' => CustomPetitionPropertyType::SUBTITLE, 'ordering' => 15, 'name' => 'Zwaarte'],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 16, 'name' => 'A', 'grouping' => 2],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 17, 'name' => 'B', 'grouping' => 2],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 18, 'name' => 'C', 'grouping' => 2],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 19, 'name' => 'D', 'grouping' => 2],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 20, 'name' => 'E', 'grouping' => 2],
                ))
                ->create(['petition_type_id' => $wooPetitionType->id]);
        }
    }

    private function createBeroepCustomPetitionProperties(): void
    {
        $beroepPetitionTypes = PetitionType::where(['type' => PetitionVariant::BEROEP->value])->get();

        foreach ($beroepPetitionTypes as $beroepPetitionType) {
            CustomPetitionProperty::factory()
                ->count(10)
                ->state(new Sequence(
                    ['type' => CustomPetitionPropertyType::NAME, 'ordering' => 1, 'name' => 'Uitkomst'],
                    ['type' => CustomPetitionPropertyType::TITLE, 'ordering' => 2, 'name' => 'Uitkomst doorgeven'],
                    ['type' => CustomPetitionPropertyType::SUBTITLE, 'ordering' => 3, 'name' => 'Uitspraak'],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 4, 'name' => 'Gegrond', 'grouping' => 3],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 5, 'name' => 'Ongegrond', 'grouping' => 3],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 6, 'name' => 'Intrekking', 'grouping' => 3],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 7, 'name' => 'Niet-ontvankelijk', 'grouping' => 3],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 8, 'name' => 'Kennelijk niet-ontvankelijk', 'grouping' => 3],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 9, 'name' => 'Toegewezen', 'grouping' => 3],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 10, 'name' => 'Afgewezen', 'grouping' => 3],
                ))
                ->create(['petition_type_id' => $beroepPetitionType->id]);
        }
    }

    private function createBezwaarCustomPetitionProperties(): void
    {
        $bezwaarPetitionTypes = PetitionType::where(['type' => PetitionVariant::BEZWAAR->value])->get();

        foreach ($bezwaarPetitionTypes as $bezwaarPetitionType) {
            CustomPetitionProperty::factory()
                ->count(28)
                ->state(new Sequence(
                    ['type' => CustomPetitionPropertyType::NAME, 'ordering' => 1, 'name' => 'Uitkomst'],
                    ['type' => CustomPetitionPropertyType::TITLE, 'ordering' => 2, 'name' => 'Binnen/buiten termijn doorgeven'],
                    ['type' => CustomPetitionPropertyType::SUBTITLE, 'ordering' => 3, 'name' => 'Binnen/buiten termijn'],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 4, 'name' => 'Binnen wettelijke termijn', 'grouping' => 4],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 5, 'name' => 'Binnen afgesproken termijn', 'grouping' => 4],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 6, 'name' => 'Buiten wettelijke/afgesproken termijn', 'grouping' => 4],
                    ['type' => CustomPetitionPropertyType::TITLE, 'ordering' => 7, 'name' => 'Uitkomst doorgeven'],
                    ['type' => CustomPetitionPropertyType::SUBTITLE, 'ordering' => 8, 'name' => 'Dictum'],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 9, 'name' => 'Gegrond', 'grouping' => 5],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 10, 'name' => 'Kennelijk gegrond', 'grouping' => 5],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 11, 'name' => 'Ongegrond', 'grouping' => 5],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 12, 'name' => 'Kennelijk ongegrond', 'grouping' => 5],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 13, 'name' => 'Niet-ontvankelijk', 'grouping' => 5],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 14, 'name' => 'Kennelijk niet-ontvankelijk', 'grouping' => 5],
                    ['type' => CustomPetitionPropertyType::SUBTITLE, 'ordering' => 15, 'name' => 'Doorzending'],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 16, 'name' => 'Doorzending', 'grouping' => 6],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 17, 'name' => 'Intrekking bezwaar', 'grouping' => 6],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 18, 'name' => 'Herziening – herstel bezwaar', 'grouping' => 6],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 19, 'name' => 'Herziening – herstel primair besluit', 'grouping' => 6],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 20, 'name' => 'Informeel', 'grouping' => 6],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 21, 'name' => 'Overig', 'grouping' => 6],
                    ['type' => CustomPetitionPropertyType::TITLE, 'ordering' => 22, 'name' => 'Zwaarte doorgeven'],
                    ['type' => CustomPetitionPropertyType::SUBTITLE, 'ordering' => 23, 'name' => 'Zwaarte'],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 24, 'name' => 'A', 'grouping' => 7],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 25, 'name' => 'B', 'grouping' => 7],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 26, 'name' => 'C', 'grouping' => 7],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 27, 'name' => 'D', 'grouping' => 7],
                    ['type' => CustomPetitionPropertyType::OPTION, 'ordering' => 28, 'name' => 'E', 'grouping' => 7],
                ))
                ->create(['petition_type_id' => $bezwaarPetitionType->id]);
        }
    }

    public function createPetitionTypes(Department $department): PetitionTypeCollection
    {
        $types = PetitionType::factory()
            ->count(3)
            ->recycle($department)
            ->state(new Sequence(
                [
                    'name' => 'Beroep',
                    'type' => PetitionVariant::BEROEP->value,
                ],
                [
                    'name' => 'Bezwaar',
                    'type' => PetitionVariant::BEZWAAR->value,
                ],
                [
                    'name' => 'Woo verzoek',
                    'type' => PetitionVariant::WOO_VERZOEK->value,
                ],
            ))
            ->create();

        return new PetitionTypeCollection($types);
    }

    private function createTermTypeSettings(Department $department): void
    {
        $departmentTermTypeSettings = [
            [TermType::FIRST, 'start_date', true, null],
            [TermType::FIRST, 'duration_in_days', true, 14],
            [TermType::FIRST, 'penalty_amount_in_euros', false, null],
            [TermType::FIRST, 'penalty_terms', false, null],
            [TermType::FIRST, 'end_date', false, null],
            [TermType::FIRST, 'date_appealed_decision', false, null],
            [TermType::SECOND, 'start_date', false, null],
            [TermType::SECOND, 'duration_in_days', true, 14],
            [TermType::SECOND, 'penalty_amount_in_euros', false, null],
            [TermType::SECOND, 'penalty_terms', false, null],
            [TermType::SECOND, 'end_date', false, null],
            [TermType::SECOND, 'date_appealed_decision', false, null],
            [TermType::THIRD, 'start_date', false, null],
            [TermType::THIRD, 'duration_in_days', false, null],
            [TermType::THIRD, 'penalty_amount_in_euros', false, null],
            [TermType::THIRD, 'penalty_terms', false, null],
            [TermType::THIRD, 'end_date', true, 14],
            [TermType::THIRD, 'date_appealed_decision', true, 14],
            [TermType::SUSPENSION, 'start_date', true, null],
            [TermType::SUSPENSION, 'duration_in_days', true, 28],
            [TermType::SUSPENSION, 'penalty_amount_in_euros', false, null],
            [TermType::SUSPENSION, 'penalty_terms', false, null],
            [TermType::SUSPENSION, 'end_date', false, null],
            [TermType::SUSPENSION, 'date_appealed_decision', false, null],
            [TermType::SPECIFIED_ADJOURNMENT, 'start_date', true, null],
            [TermType::SPECIFIED_ADJOURNMENT, 'duration_in_days', true, 28],
            [TermType::SPECIFIED_ADJOURNMENT, 'penalty_amount_in_euros', false, null],
            [TermType::SPECIFIED_ADJOURNMENT, 'penalty_terms', false, null],
            [TermType::SPECIFIED_ADJOURNMENT, 'end_date', false, null],
            [TermType::SPECIFIED_ADJOURNMENT, 'date_appealed_decision', false, null],
            [TermType::NOTICE_OF_DEFAULT, 'start_date', true, null],
            [TermType::NOTICE_OF_DEFAULT, 'duration_in_days', true, 28],
            [TermType::NOTICE_OF_DEFAULT, 'penalty_amount_in_euros', false, null],
            [TermType::NOTICE_OF_DEFAULT, 'penalty_terms', true, $this->createPenaltyAmountInEurosValue(3, 14, 100)],
            [TermType::NOTICE_OF_DEFAULT, 'end_date', false, null],
            [TermType::NOTICE_OF_DEFAULT, 'date_appealed_decision', false, null],
            [TermType::APPEAL_NOT_TIMELY, 'start_date', true, null],
            [TermType::APPEAL_NOT_TIMELY, 'duration_in_days', true, 28],
            [TermType::APPEAL_NOT_TIMELY, 'penalty_amount_in_euros', false, null],
            [TermType::APPEAL_NOT_TIMELY, 'penalty_terms', true, $this->createPenaltyAmountInEurosValue(1, 14, 100)],
            [TermType::APPEAL_NOT_TIMELY, 'end_date', false, null],
            [TermType::APPEAL_NOT_TIMELY, 'date_appealed_decision', false, null],
            [TermType::COMMITTEE_HEARING, 'start_date', true, null],
            [TermType::COMMITTEE_HEARING, 'duration_in_days', true, 42],
            [TermType::COMMITTEE_HEARING, 'penalty_amount_in_euros', false, null],
            [TermType::COMMITTEE_HEARING, 'penalty_terms', false, null],
            [TermType::COMMITTEE_HEARING, 'end_date', false, null],
            [TermType::COMMITTEE_HEARING, 'date_appealed_decision', true, null],
            [TermType::OBJECTION_PERIOD, 'start_date', true, null],
            [TermType::OBJECTION_PERIOD, 'duration_in_days', true, 42],
            [TermType::OBJECTION_PERIOD, 'penalty_amount_in_euros', false, null],
            [TermType::OBJECTION_PERIOD, 'penalty_terms', false, null],
            [TermType::OBJECTION_PERIOD, 'end_date', false, null],
            [TermType::OBJECTION_PERIOD, 'date_appealed_decision', true, null],
            [TermType::PENALTY, 'start_date', false, null],
            [TermType::PENALTY, 'duration_in_days', false, 14],
            [TermType::PENALTY, 'penalty_amount_in_euros', true, 100],
            [TermType::PENALTY, 'end_date', true, null],
            [TermType::PENALTY, 'date_appealed_decision', false, null],
        ];

        DepartmentTermTypeSetting::factory()
            ->count(count($departmentTermTypeSettings))
            ->sequence(function (Sequence $sequence) use ($departmentTermTypeSettings) {
                return [
                    'term_type' => $departmentTermTypeSettings[$sequence->index][0],
                    'field' => $departmentTermTypeSettings[$sequence->index][1],
                    'active' => $departmentTermTypeSettings[$sequence->index][2],
                    'default_value' => $departmentTermTypeSettings[$sequence->index][3],
                    'title' => null,
                ];
            })
            ->create([
                'department_id' => $department->id,
            ]);
    }

    private function createPenaltyAmountInEurosValue(int $amount, int $durationInDays, int $penaltyAmountInEuros): string
    {
        $penaltyAmountInEurosValue = [];

        for ($i = 1; $i <= $amount; $i++) {
            $penaltyAmountInEurosValue[] = [
                'duration_in_days' => $durationInDays,
                'penalty_amount_in_euros' => $penaltyAmountInEuros,
            ];
        }

        return json_encode($penaltyAmountInEurosValue);
    }

    private function creatPolicyDepartments(): PolicyDepartmentCollection
    {
        $departmentNames = [
            'aCBG',
            'BDz',
            'BPZ',
            'CAK',
            'CBG',
            'CCMO',
            'CIBG',
            'CIZ',
            'CSZ',
            'CZ',
            'DCo',
            'DI/CIO',
            'DJ',
            'DMO',
            'Dopingautoriteit',
            'DUS-I',
            'ESTT',
            'FEZ',
            'GMT',
            'GR',
            'IGJ',
            'IZ',
            'IZB',
            'Lcsh',
            'LZ',
            'MEVA',
            'NLsportraad',
            'NZa',
            'OBP',
            'PDIZA',
            'PDO',
            'PG',
            'PGB',
            'PMI',
            'PUR',
            'PZo',
            'RIVM',
            'RVS',
            'SB',
            'SCP',
            'VGP',
            'WJZ',
            'Z',
            'ZIN',
            'ZJCN',
            'ZonMW',
        ];


        $policyDepartments = PolicyDepartment::factory()
            ->count(count($departmentNames))
            ->sequence(fn(Sequence $sequence) => ['name' => $departmentNames[$sequence->index]])
            ->create();

        return new PolicyDepartmentCollection($policyDepartments);
    }

    /**
     * @return Collection<int, Contact>
     */
    public function createContacts(Department $department): Collection
    {
        return Contact::factory()
            ->count(20)
            ->recycle($department)
            ->create();
    }

    public function createTeams(Department $department): Collection
    {
        return Team::factory()
            ->count(2)
            ->recycle($department)
            ->create();
    }

    /**
     * @param Collection<array-key, PetitionType> $petitionTypes
     *
     * @return Collection<array-key, PetitionExport>
     */
    public function createExports(Department $department, Collection $petitionTypes): Collection
    {
        return PetitionExport::factory()
            ->count(10)
            ->recycle([$department, $petitionTypes])
            ->create();
    }

    /**
     * @param Collection<int, Contact> $contacts
     */
    public function createPetitions(
        Department $department,
        UserCollection $users,
        Collection $contacts,
        PetitionStatusCollection $petitionStatuses,
        PetitionTypeCollection $petitionTypes,
        PetitionCategoryCollection $petitionCategories,
        PolicyDepartmentCollection $policyDepartments,
    ): void {
        Petition::factory()
            ->count(21)
            ->recycle([$department, $users, $contacts, $petitionStatuses, $petitionTypes, $petitionCategories])
            ->withFirstAssignee()
            ->create()
            ->each(static function (Petition $petition) use ($policyDepartments, $petitionStatuses): void {
                $petitionPolicyDepartments = $policyDepartments->random(rand(0, 3));
                $petition->policyDepartments()->attach($petitionPolicyDepartments);
                PetitionStatusHistory::factory()->recycle([$petition, $petitionStatuses])->count(rand(1, 11))->create();
            });
    }

    private function createCustomDateLabelsPerPetitionType(PetitionTypeCollection $petitionTypes): void
    {
        $customDateLabels = collect(CustomDateLabel::cases());

        foreach ($petitionTypes as $petitionType) {
            $randomAmount = rand(0, 3);

            if ($randomAmount === 0) {
                continue;
            }

            $randomLabels = $customDateLabels->random($randomAmount)->map(fn($label) => ['date_label' => $label->value])->toArray();

            PetitionTypeCustomDateLabel::factory()
                ->count($randomAmount)
                ->recycle($petitionType)
                ->state(new Sequence(
                    ...$randomLabels,
                ))
                ->create();
        }
    }

    private function createPetitionCategories(mixed $department): PetitionCategoryCollection
    {
        $categories = PetitionCategory::factory()
            ->count(3)
            ->recycle($department)
            ->create();

        return new PetitionCategoryCollection($categories);
    }
}
