<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Petition\WordTemplate;

use App\Facades\DisplayDate;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PolicyDepartment;
use App\Services\Petition\WordTemplate\WordTemplateReplacementsMapper;
use App\ValueObjects\Address;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
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
            ]);

        $expected = [
            'ACT_datum' => CarbonImmutable::now()->format('d-m-Y'),
            'BEZ_kenmerk_gemachtigde' => $petition->message,
            'BEZ_dtBezwaar' => $petition->date_of_message->format('d-m-Y'),
            'BEZ_dtOntvangst' => $petition->date_of_message->format('d-m-Y'),
            'COR_ANH_regel1' => 'Geachte',
            'COR_datum' => CarbonImmutable::now()->format('d-m-Y'),
            'DOS_naam' => $petition->name,
            'DOS_nummer' => $petition->number,
            'COR_onsKenmerk' => $petition->number,
            'BZ_BEZ_postkamer' => $petition->policyDepartments->first()->name,
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
            ]);

        $expected = [
            'ACT_datum' => CarbonImmutable::now()->format('d-m-Y'),
            'BEZ_kenmerk_gemachtigde' => $petition->message,
            'BEZ_dtBezwaar' => $petition->date_of_message->format('d-m-Y'),
            'BEZ_dtOntvangst' => $petition->date_of_message->format('d-m-Y'),
            'COR_ANH_regel1' => 'Geachte',
            'COR_datum' => CarbonImmutable::now()->format('d-m-Y'),
            'DOS_naam' => $petition->name,
            'DOS_nummer' => $petition->number,
            'COR_onsKenmerk' => $petition->number,
            'BZ_BEZ_postkamer' => $petition->policyDepartments->toString(),
        ];

        $mapper = $this->getWordTemplateReplacementsMapper();
        $this->assertEquals($expected, $mapper->map($petition));
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
        ]);

        $expected = [
            'ACT_datum' => CarbonImmutable::now()->format('d-m-Y'),
            'BEZ_kenmerk_gemachtigde' => $petition->message,
            'BEZ_dtBezwaar' => $petition->date_of_message->format('d-m-Y'),
            'BEZ_dtOntvangst' => $petition->date_of_message->format('d-m-Y'),
            'COR_ANH_regel1' => 'Geachte',
            'COR_datum' => CarbonImmutable::now()->format('d-m-Y'),
            'DOS_naam' => $petition->name,
            'DOS_nummer' => $petition->number,
            'COR_onsKenmerk' => $petition->number,
        ];

        $mapper = $this->getWordTemplateReplacementsMapper();
        $this->assertEquals($expected, $mapper->map($petition));
    }

    public function testMapReplacementBEZDtOntvangstIsSet(): void
    {
        $dateOfMessage = $this->faker->calendarDate();
        $petition = Petition::factory()->create([
            'message' => $this->faker->sentence(),
            'date_of_message' => $dateOfMessage,
        ]);

        $mapper = $this->getWordTemplateReplacementsMapper();
        $result = $mapper->map($petition);

        $this->assertEquals(DisplayDate::date($dateOfMessage), $result['BEZ_dtOntvangst']);
    }

    /**
     * @param array<mixed> $petitionAttributes
     */
    #[DataProvider('replacementDataProvider')]
    public function testMapReplacementBEZDtOntvangstNotSet(array $petitionAttributes): void
    {
        $petition = Petition::factory()->create($petitionAttributes);

        $mapper = $this->getWordTemplateReplacementsMapper();
        $result = $mapper->map($petition);

        $this->assertArrayNotHasKey('BEZ_dtOntvangst', $result);
    }

    public static function replacementDataProvider(): array
    {
        return [
            'no message' => [['date_of_message' => '2000-01-01', 'message' => null]],
            'no date_of_message' => [['date_of_message' => null, 'message' => 'my message']],
            'no date_of_message, no message' => [['date_of_message' => null, 'message' => null]],
        ];
    }

    public function testMapWithApplicantAndAndRepresentative(): void
    {
        $department = Department::factory()->create();
        $applicant = Contact::factory()->recycle($department)->create();
        $representative = Contact::factory()->recycle($department)->create();

        $petition = Petition::factory()->recycle($department)->create();
        $petition->contacts()->attach($applicant, ['role' => 'applicant']);
        $petition->contacts()->attach($representative, ['role' => 'representative']);

        $mapper = $this->getWordTemplateReplacementsMapper();
        $result = $mapper->map($petition);

        $this->assertEquals(Str::address(Address::fromContact($representative)), $result['COR_ADR_regel1']);
    }

    public function testMapWithApplicantAndWithoutRepresentative(): void
    {
        $department = Department::factory()->create();
        $applicant = Contact::factory()->recycle($department)->create();

        $petition = Petition::factory()->recycle($department)->create();
        $petition->contacts()->attach($applicant, ['role' => 'applicant']);

        $mapper = $this->getWordTemplateReplacementsMapper();
        $result = $mapper->map($petition);

        $this->assertEquals(Str::address(Address::fromContact($applicant)), $result['COR_ADR_regel1']);
    }

    public function testMapWithRepresentativeAndWithoutApplicant(): void
    {
        $department = Department::factory()->create();
        $representative = Contact::factory()->recycle($department)->create();

        $petition = Petition::factory()->recycle($department)->create();
        $petition->contacts()->attach($representative, ['role' => 'representative']);

        $mapper = $this->getWordTemplateReplacementsMapper();
        $result = $mapper->map($petition);

        $this->assertEquals(Str::address(Address::fromContact($representative)), $result['COR_ADR_regel1']);
    }

    public function testMapWithDecisionFields(): void
    {
        $decisionReference = $this->faker->regexify('BEZ-[0-9]{4}-[A-Z]{2}');
        $decisionDate = $this->faker->calendarDate();

        $petition = Petition::factory()->create([
            'message' => $this->faker->sentence(),
            'date_of_message' => $this->faker->calendarDate(),
            'decision_reference' => $decisionReference,
            'decision_date' => $decisionDate,
            'applicant_id' => null,
            'representative_id' => null,
        ]);

        $mapper = $this->getWordTemplateReplacementsMapper();
        $result = $mapper->map($petition);

        $this->assertArrayHasKey('BEZ_kenmerk_besluit', $result);
        $this->assertEquals($decisionReference, $result['BEZ_kenmerk_besluit']);

        $this->assertArrayHasKey('DOS_dtBesluit', $result);
        $this->assertEquals($decisionDate->format('d-m-Y'), $result['DOS_dtBesluit']);
    }

    private function getWordTemplateReplacementsMapper(): WordTemplateReplacementsMapper
    {
        return $this->app->get(WordTemplateReplacementsMapper::class);
    }
}
