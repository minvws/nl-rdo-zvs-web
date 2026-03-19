<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Petition\WordTemplate;

use App\Enums\CustomDateLabel;
use App\Facades\DisplayDate;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionCustomDate;
use App\Models\PolicyDepartment;
use App\Models\User;
use App\Services\Petition\WordTemplate\WordTemplateReplacementsMapper;
use App\ValueObjects\Address;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Tests\Feature\FeatureTestCase;

class WordTemplateReplacementsMapperTest extends FeatureTestCase
{
    public function testMapWithPolicyDepartment(): void
    {
        $petition = Petition::factory()
            ->has(PolicyDepartment::factory())
            ->create([
                'message' => $this->faker->sentence(),
                'applicant_id' => null,
                'representative_id' => null,
                'date_of_message' => $this->faker->calendarDate(),
                'decision_reference' => null,
                'decision_date' => null,
                'assigned_to' => null,
            ]);

        $expected = [
            'BELEIDSDIRECTIE' => $petition->policyDepartments->first()->name,
            'DATUM_BESLISSING_OP_BEZWAAR' => 'DATUM_BESLISSING_OP_BEZWAAR',
            'DATUM_BESTREDEN_BESLUIT' => 'DATUM_BESTREDEN_BESLUIT',
            'DATUM_ONTVANGEN_BERICHT' => DisplayDate::date($petition->date_of_message),
            'EMAIL_ADRES' => 'EMAIL_ADRES',
            'KENMERK_ONTVANGEN_BERICHT' => $petition->message,
            'KENMERK_ZVS_NUMMER' => $petition->number,
            'NAAM_BEHANDELAAR' => 'NAAM_BEHANDELAAR',
            'NAAM_BEZWAARDE' => 'NAAM_BEZWAARDE',
            'NAAM_CONTACT' => 'NAAM_CONTACT',
            'NAAM_ZAAK' => $petition->name,
            'TELEFOON_CONTACT' => 'TELEFOON_CONTACT',
            'VANDAAG' => DisplayDate::date(CarbonImmutable::now()),
        ];

        $mapper = $this->getWordTemplateReplacementsMapper();
        $this->assertEquals($expected, $mapper->map($petition));
    }

    public function testMapWithMultiplePolicyDepartments(): void
    {
        $petition = Petition::factory()
            ->has(PolicyDepartment::factory()->count($this->faker->numberBetween(2, 5)))
            ->create([
                'message' => $this->faker->sentence(),
                'applicant_id' => null,
                'representative_id' => null,
                'date_of_message' => $this->faker->calendarDate(),
                'decision_reference' => null,
                'decision_date' => null,
                'assigned_to' => null,
            ]);

        $mapper = $this->getWordTemplateReplacementsMapper();
        $result = $mapper->map($petition);

        $this->assertEquals($petition->policyDepartments()->first()->name, $result['BELEIDSDIRECTIE']);
    }

    public function testMapWithoutPolicyDepartment(): void
    {
        $petition = Petition::factory()->create([
            'message' => $this->faker->sentence(),
            'applicant_id' => null,
            'representative_id' => null,
            'date_of_message' => $this->faker->calendarDate(),
            'decision_reference' => null,
            'decision_date' => null,
            'assigned_to' => null,
        ]);

        $mapper = $this->getWordTemplateReplacementsMapper();
        $result = $mapper->map($petition);

        $this->assertEquals('BELEIDSDIRECTIE', $result['BELEIDSDIRECTIE']);
    }

    public function testMapDateOnvangenBerichtIsSet(): void
    {
        $dateOfMessage = $this->faker->calendarDate();
        $petition = Petition::factory()->create([
            'date_of_message' => $dateOfMessage,
        ]);

        $mapper = $this->getWordTemplateReplacementsMapper();
        $result = $mapper->map($petition);

        $this->assertEquals(DisplayDate::date($dateOfMessage), $result['DATUM_ONTVANGEN_BERICHT']);
    }

    public function testMapDateOnvangenBerichtFallsBackToPlaceholder(): void
    {
        $petition = Petition::factory()->create([
            'date_of_message' => null,
        ]);

        $mapper = $this->getWordTemplateReplacementsMapper();
        $result = $mapper->map($petition);

        $this->assertEquals('DATUM_ONTVANGEN_BERICHT', $result['DATUM_ONTVANGEN_BERICHT']);
    }

    public function testMapWithApplicantAndRepresentative(): void
    {
        $department = Department::factory()->create();
        $applicant = Contact::factory()->recycle($department)->create();
        $representative = Contact::factory()->recycle($department)->create();

        $petition = Petition::factory()->recycle($department)->create();
        $petition->contacts()->attach($applicant, ['role' => 'applicant']);
        $petition->contacts()->attach($representative, ['role' => 'representative']);

        $mapper = $this->getWordTemplateReplacementsMapper();
        $result = $mapper->map($petition);

        $this->assertEquals(Str::address(Address::fromContact($representative)), $result['NAAM_ADRES']);
        $this->assertEquals($representative->full_name, $result['NAAM_CONTACT']);
        $this->assertEquals($applicant->full_name, $result['NAAM_BEZWAARDE']);
    }

    public function testMapWithApplicantAndWithoutRepresentative(): void
    {
        $department = Department::factory()->create();
        $applicant = Contact::factory()->recycle($department)->create();

        $petition = Petition::factory()->recycle($department)->create();
        $petition->contacts()->attach($applicant, ['role' => 'applicant']);

        $mapper = $this->getWordTemplateReplacementsMapper();
        $result = $mapper->map($petition);

        $this->assertEquals(Str::address(Address::fromContact($applicant)), $result['NAAM_ADRES']);
        $this->assertEquals($applicant->full_name, $result['NAAM_CONTACT']);
        $this->assertEquals($applicant->full_name, $result['NAAM_BEZWAARDE']);
    }

    public function testMapWithRepresentativeAndWithoutApplicant(): void
    {
        $department = Department::factory()->create();
        $representative = Contact::factory()->recycle($department)->create();

        $petition = Petition::factory()->recycle($department)->create();
        $petition->contacts()->attach($representative, ['role' => 'representative']);

        $mapper = $this->getWordTemplateReplacementsMapper();
        $result = $mapper->map($petition);

        $this->assertEquals(Str::address(Address::fromContact($representative)), $result['NAAM_ADRES']);
        $this->assertEquals($representative->full_name, $result['NAAM_CONTACT']);
        $this->assertEquals('NAAM_BEZWAARDE', $result['NAAM_BEZWAARDE']);
    }

    public function testMapWithDecisionFields(): void
    {
        $decisionReference = $this->faker->regexify('BEZ-[0-9]{4}-[A-Z]{2}');
        $decisionDate = $this->faker->calendarDate();

        $petition = Petition::factory()->create([
            'decision_reference' => $decisionReference,
            'decision_date' => $decisionDate,
            'applicant_id' => null,
            'representative_id' => null,
        ]);

        $mapper = $this->getWordTemplateReplacementsMapper();
        $result = $mapper->map($petition);

        $this->assertEquals($decisionReference, $result['KENMERK_BESTREDEN_BESLUIT']);
        $this->assertEquals(DisplayDate::date($decisionDate), $result['DATUM_BESTREDEN_BESLUIT']);
    }

    public function testMapWithAssignedUser(): void
    {
        $user = User::factory()->create();

        $petition = Petition::factory()->create([
            'assigned_to' => $user->id,
            'applicant_id' => null,
            'representative_id' => null,
        ]);

        $mapper = $this->getWordTemplateReplacementsMapper();
        $result = $mapper->map($petition);

        $this->assertEquals($user->name, $result['NAAM_BEHANDELAAR']);
    }

    public function testMapWithoutAssignedUser(): void
    {
        $petition = Petition::factory()->create([
            'assigned_to' => null,
            'applicant_id' => null,
            'representative_id' => null,
        ]);

        $mapper = $this->getWordTemplateReplacementsMapper();
        $result = $mapper->map($petition);

        $this->assertEquals('NAAM_BEHANDELAAR', $result['NAAM_BEHANDELAAR']);
    }

    public function testMapWithDateDecisionOnAppeal(): void
    {
        $date = $this->faker->calendarDate();

        $petition = Petition::factory()->create([
            'applicant_id' => null,
            'representative_id' => null,
        ]);

        PetitionCustomDate::factory()->create([
            'petition_id' => $petition->id,
            'date_label' => CustomDateLabel::DATE_DECISION_ON_APPEAL,
            'date' => $date,
        ]);

        $mapper = $this->getWordTemplateReplacementsMapper();
        $result = $mapper->map($petition->refresh());

        $this->assertEquals(DisplayDate::date($date), $result['DATUM_BESLISSING_OP_BEZWAAR']);
    }

    public function testMapWithoutDateDecisionOnAppeal(): void
    {
        $petition = Petition::factory()->create([
            'applicant_id' => null,
            'representative_id' => null,
        ]);

        $mapper = $this->getWordTemplateReplacementsMapper();
        $result = $mapper->map($petition);

        $this->assertEquals('DATUM_BESLISSING_OP_BEZWAAR', $result['DATUM_BESLISSING_OP_BEZWAAR']);
    }

    private function getWordTemplateReplacementsMapper(): WordTemplateReplacementsMapper
    {
        return $this->app->get(WordTemplateReplacementsMapper::class);
    }
}
