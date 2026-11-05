<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Seeders;

use App\Enums\HearingForm;
use App\Enums\PetitionEventType;
use App\Enums\PetitionVariant;
use App\Enums\ResultType;
use App\Enums\SuspensionType;
use App\Models\Petition;
use App\Services\PetitionEventAvailabilityService;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\WizardEventCollection;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\TermEngineV2PetitionSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function array_map;
use function array_unique;
use function array_values;
use function count;
use function sprintf;

class TermEngineV2PetitionSeederTest extends FeatureTestCase
{
    #[Test]
    public function testSeedsAPetitionPerScenarioWithTermEngineV2Events(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (TermEngineV2PetitionSeeder::scenarios() as $scenario) {
            $petition = Petition::query()->where('name', $scenario['title'])->sole();

            $this->assertTrue($petition->isTermEngineConverted(), $scenario['name']);
            $this->assertCount(count($scenario['events']), $petition->petitionEvents, $scenario['name']);
            $this->assertSame(0, $petition->petitionTerms()->count(), $scenario['name']);
            $this->assertSame("Team test scenario's", $petition->team->name, $scenario['name']);
            $this->assertSame($scenario['description'], $petition->description, $scenario['name']);
        }
    }

    #[Test]
    public function testScenarioNamesAreUnique(): void
    {
        $names = array_map(
            static fn (array $scenario): string => $scenario['name'],
            TermEngineV2PetitionSeeder::scenarios(),
        );

        $this->assertSame($names, array_values(array_unique($names)));
    }

    #[Test]
    public function testEveryScenarioFollowsTheRulesOfTheEventWizard(): void
    {
        $service = new PetitionEventAvailabilityService();

        foreach (TermEngineV2PetitionSeeder::scenarios() as $scenario) {
            $events = WizardEventCollection::make();

            foreach ($scenario['events'] as $event) {
                $available = $service->getAvailableEventTypes($scenario['variant'], $events);

                $this->assertContains(
                    $event['type'],
                    $available,
                    sprintf('%s: %s is not available at this point', $scenario['name'], $event['type']->value),
                );

                $date = CalendarDate::instance(CarbonImmutable::now()->subDays($event['days_ago']));

                $events = $events->add(new PetitionEventData(
                    type: $event['type'],
                    date: $date,
                    createdAt: CarbonImmutable::now(),
                    duration: $event['duration'] ?? null,
                    suspensionType: $event['suspension_type'] ?? null,
                    resultType: $event['result_type'] ?? null,
                    hearingForm: $event['hearing_form'] ?? null,
                    reasoning: $event['reasoning'] ?? null,
                    withdrawalDuration: $event['withdrawal_duration'] ?? null,
                ));
            }
        }
    }

    #[Test]
    public function testOnlyUsesResultTypesThatBelongToThePetitionVariant(): void
    {
        foreach (TermEngineV2PetitionSeeder::scenarios() as $scenario) {
            foreach ($scenario['events'] as $event) {
                if (!isset($event['result_type'])) {
                    continue;
                }

                $this->assertContains(
                    $event['result_type'],
                    ResultType::getForPetitionType($scenario['variant']),
                    $scenario['name'],
                );
            }
        }
    }

    #[Test]
    public function testCoversAllResultTypesOfEachPetitionVariant(): void
    {
        $used = [];
        foreach (TermEngineV2PetitionSeeder::scenarios() as $scenario) {
            foreach ($scenario['events'] as $event) {
                if (isset($event['result_type'])) {
                    $used[$scenario['variant']->value][$event['result_type']->value] = true;
                }
            }
        }

        foreach ([PetitionVariant::BEZWAAR, PetitionVariant::WOO_VERZOEK, PetitionVariant::KLACHT] as $variant) {
            foreach (ResultType::getForPetitionType($variant) as $resultType) {
                $this->assertArrayHasKey($resultType->value, $used[$variant->value] ?? [], $variant->value);
            }
        }
    }

    #[Test]
    public function testCoversEveryEventTypeSuspensionTypeAndHearingForm(): void
    {
        $eventTypes = [];
        $suspensionTypes = [];
        $hearingForms = [];
        foreach (TermEngineV2PetitionSeeder::scenarios() as $scenario) {
            foreach ($scenario['events'] as $event) {
                $eventTypes[$event['type']->value] = true;
                $suspensionTypes[($event['suspension_type'] ?? null)?->value] = true;
                $hearingForms[($event['hearing_form'] ?? null)?->value] = true;
            }
        }

        foreach (PetitionEventType::cases() as $type) {
            $this->assertArrayHasKey($type->value, $eventTypes);
        }

        foreach (SuspensionType::cases() as $type) {
            $this->assertArrayHasKey($type->value, $suspensionTypes);
        }

        foreach (HearingForm::cases() as $form) {
            $this->assertArrayHasKey($form->value, $hearingForms);
        }
    }
}
