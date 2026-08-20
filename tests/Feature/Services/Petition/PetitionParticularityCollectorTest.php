<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Petition;

use App\Enums\PetitionEventType;
use App\Enums\SuspensionType;
use App\Enums\TermType;
use App\Models\Builder\Petition\PetitionQueryBuilder;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionEvent;
use App\Models\PetitionType;
use App\Services\Petition\PetitionParticularityCollector;
use App\ValueObjects\CalendarDate;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function array_merge;

class PetitionParticularityCollectorTest extends FeatureTestCase
{
    public function testLabelsReferencedPetitionFromOtherDepartment(): void
    {
        $label = $this->faker->word();

        $petitionType = PetitionType::factory()->create([
            'particularity_label' => $label,
        ]);
        $dep1 = Department::factory()->create();
        $dep2 = Department::factory()->create();

        $petition1 = Petition::factory()->recycle($dep1)->create();
        $petition2 = Petition::factory()->recycle($dep2)->recycle($petitionType)->create();

        DB::table('petition_petition')->insert([
            'petition_id' => $petition1->id,
            'related_petition_id' => $petition2->id,
        ]);

        $collector = $this->app->make(PetitionParticularityCollector::class);

        $labels = $collector->collectParticularities($petition1);
        $this->assertEquals([$label], $labels);
    }

    public function testLabelsWithActualSuspension(): void
    {
        $department = Department::factory()->create();

        $petition = Petition::factory()->recycle($department)->create();

        $petition->petitionTerms()->create([
            'id' => $this->faker->uuid(),
            'type' => TermType::SUSPENSION,
            'start_date' => CalendarDate::today(),
            'duration_in_days' => $this->faker->numberBetween(1, 100),
            'penalty_amount_in_euros' => $this->faker->numberBetween(1, 100),
        ]);

        $collector = $this->app->make(PetitionParticularityCollector::class);

        $labels = $collector->collectParticularities($petition);

        $this->assertEquals(['Opschorting'], $labels);
    }

    public function testLabelsWithActualAdjournment(): void
    {
        $department = Department::factory()->create();

        $petition = Petition::factory()->recycle($department)->create();

        $petition->petitionTerms()->create([
            'id' => $this->faker->uuid(),
            'type' => TermType::SPECIFIED_ADJOURNMENT,
            'start_date' => CalendarDate::today(),
            'duration_in_days' => $this->faker->numberBetween(1, 100),
            'penalty_amount_in_euros' => $this->faker->numberBetween(1, 100),
        ]);

        $collector = $this->app->make(PetitionParticularityCollector::class);

        $labels = $collector->collectParticularities($petition);

        $this->assertEquals(['Aanhouding'], $labels);
    }

    public function testLabelsWithFutureDraftTerm(): void
    {
        $department = Department::factory()->create();

        $petition = Petition::factory()->recycle($department)->create();

        $startDate = CalendarDate::today()->addDay();

        $petition->draftTerm()->create([
            'id' => $this->faker->uuid(),
            'start_date' => $startDate,
            'days_after_event' => $this->faker->numberBetween(0, 365),
            'days_after_date_withdrawal' => $this->faker->optional()->numberBetween(0, 365),
        ]);

        $petition->refresh();

        $collector = $this->app->make(PetitionParticularityCollector::class);

        $labels = $collector->collectParticularities($petition);

        $this->assertEquals(['Aanhouding'], $labels);
    }

    public function testLabelsWithNoticeOfDefault(): void
    {
        $department = Department::factory()->create();

        $petition = Petition::factory()->recycle($department)->create();

        $petition->petitionTerms()->create([
            'id' => $this->faker->uuid(),
            'start_date' => CalendarDate::today(),
            'type' => TermType::NOTICE_OF_DEFAULT,
            'duration_in_days' => $this->faker->numberBetween(1, 100),
            'penalty_amount_in_euros' => $this->faker->numberBetween(1, 100),
        ]);

        $collector = $this->app->make(PetitionParticularityCollector::class);

        $labels = $collector->collectParticularities($petition);

        $this->assertEquals(['IGS'], $labels);
    }

    #[Test]
    public function testLabelsWithTev2NoticeOfDefaultReceived(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        PetitionEvent::factory()->recycle($petition)->withType(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED)->create();

        $petition->refresh();

        $collector = $this->app->make(PetitionParticularityCollector::class);
        $labels = $collector->collectParticularities($petition);

        $this->assertEquals(['IGS'], $labels);
    }

    #[Test]
    public function testLabelsWithTev2NoticeOfDefaultWithdrawn(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        PetitionEvent::factory()->recycle($petition)->withType(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED)->create();
        PetitionEvent::factory()->recycle($petition)->withType(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN)->create();

        $petition->refresh();

        $collector = $this->app->make(PetitionParticularityCollector::class);
        $labels = $collector->collectParticularities($petition);

        $this->assertEquals([], $labels);
    }

    #[Test]
    public function testLabelsWithTev2MultipleReceivedOneWithdrawn(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        PetitionEvent::factory()->recycle($petition)->withType(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED)->count(2)->create();
        PetitionEvent::factory()->recycle($petition)->withType(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN)->create();

        $petition->refresh();

        $collector = $this->app->make(PetitionParticularityCollector::class);
        $labels = $collector->collectParticularities($petition);

        $this->assertEquals(['IGS'], $labels);
    }

    #[Test]
    public function testLabelsWithTev2RunningSuspension(): void
    {
        $petition = $this->petitionWithEvent(PetitionEventType::LETTER_OF_SUSPENSION_SENT, [
            'date' => CalendarDate::today()->subDays(3),
            'duration' => 10,
            'suspension_type' => SuspensionType::SUSPENSION,
        ]);

        $this->assertEquals(['Opschorting'], $this->collectFor($petition));
    }

    #[Test]
    public function testLabelsWithTev2SuspensionEndedBeforeToday(): void
    {
        $petition = $this->petitionWithEvent(PetitionEventType::LETTER_OF_SUSPENSION_SENT, [
            'date' => CalendarDate::today()->subDays(10),
            'duration' => 30,
            'suspension_type' => SuspensionType::SUSPENSION,
        ]);

        PetitionEvent::factory()->recycle($petition)->withType(PetitionEventType::SUSPENSION_END)->create([
            'date' => CalendarDate::today()->subDay(),
            'duration' => null,
        ]);

        $petition->refresh();

        $this->assertEquals([], $this->collectFor($petition));
    }

    #[Test]
    public function testLabelsWithTev2ExpiredSuspension(): void
    {
        $petition = $this->petitionWithEvent(PetitionEventType::LETTER_OF_SUSPENSION_SENT, [
            'date' => CalendarDate::today()->subDays(20),
            'duration' => 5,
            'suspension_type' => SuspensionType::SUSPENSION,
        ]);

        $this->assertEquals([], $this->collectFor($petition));
    }

    #[Test]
    public function testLabelsWithTev2RunningSpecifiedAdjournment(): void
    {
        $petition = $this->petitionWithEvent(PetitionEventType::LETTER_OF_SUSPENSION_SENT, [
            'date' => CalendarDate::today()->subDays(3),
            'duration' => 10,
            'suspension_type' => SuspensionType::SPECIFIED_ADJOURNMENT,
        ]);

        $this->assertEquals(['Aanhouding'], $this->collectFor($petition));
    }

    #[Test]
    public function testLabelsWithTev2OpenUnspecifiedAdjournment(): void
    {
        $petition = $this->petitionWithEvent(PetitionEventType::UNSPECIFIED_ADJOURNMENT, [
            'date' => CalendarDate::today()->subDays(3),
        ]);

        $this->assertEquals(['Aanhouding'], $this->collectFor($petition));
    }

    #[Test]
    public function testLabelsWithTev2EndedUnspecifiedAdjournment(): void
    {
        $petition = $this->petitionWithEvent(PetitionEventType::UNSPECIFIED_ADJOURNMENT, [
            'date' => CalendarDate::today()->subDays(3),
        ]);

        PetitionEvent::factory()->recycle($petition)->withType(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END)->create([
            'date' => CalendarDate::today()->subDay(),
        ]);

        $petition->refresh();

        $this->assertEquals([], $this->collectFor($petition));
    }

    #[Test]
    public function testLabelsWithTev2RunningAppealNotTimelyTerm(): void
    {
        $petition = $this->petitionWithEvent(PetitionEventType::APPEAL_DECISION_NOT_TIMELY, [
            'date' => CalendarDate::today()->subDays(2),
            'duration' => 14,
        ]);

        $this->assertEquals(['BNT'], $this->collectFor($petition));
    }

    #[Test]
    public function testLabelsWithTev2PassedAppealNotTimelyTerm(): void
    {
        $petition = $this->petitionWithEvent(PetitionEventType::APPEAL_DECISION_NOT_TIMELY, [
            'date' => CalendarDate::today()->subDays(20),
            'duration' => 14,
        ]);

        $this->assertEquals([], $this->collectFor($petition));
    }

    #[Test]
    public function testLabelsWithTev2AppealNotTimelyWithoutDuration(): void
    {
        $petition = $this->petitionWithEvent(PetitionEventType::APPEAL_DECISION_NOT_TIMELY, [
            'date' => CalendarDate::today(),
            'duration' => null,
        ]);

        $this->assertEquals([], $this->collectFor($petition));
    }

    #[Test]
    public function testLabelsWithTev2Adjournment(): void
    {
        $petition = $this->petitionWithEvent(PetitionEventType::ADJOURNMENT, [
            'date' => CalendarDate::today()->subDays(30),
            'duration' => 42,
        ]);

        $this->assertEquals(['Verdaging'], $this->collectFor($petition));
    }

    /**
     * The overview eager loads a subset of the event columns, so the tags have to survive that.
     *
     * @param array<string, mixed> $attributes
     * @param array<string> $expectedLabels
     */
    #[Test]
    #[DataProvider('overviewQueryParticularityProvider')]
    public function testLabelsOnAPetitionLoadedThroughTheOverviewQuery(
        PetitionEventType $type,
        array $attributes,
        int $daysAgo,
        array $expectedLabels,
    ): void {
        $petition = $this->petitionWithEvent(
            $type,
            array_merge($attributes, ['date' => CalendarDate::today()->subDays($daysAgo)]),
        );

        $loadedPetition = PetitionQueryBuilder::make()->whereKey($petition->id)->first();

        $this->assertNotNull($loadedPetition);
        $this->assertEquals($expectedLabels, $this->collectFor($loadedPetition));
    }

    /**
     * @return array<string, array{PetitionEventType, array<string, mixed>, int, array<string>}>
     */
    public static function overviewQueryParticularityProvider(): array
    {
        return [
            'notice of default' => [PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, [], 0, ['IGS']],
            'suspension' => [
                PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                ['duration' => 10, 'suspension_type' => SuspensionType::SUSPENSION],
                3,
                ['Opschorting'],
            ],
            'specified adjournment' => [
                PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                ['duration' => 10, 'suspension_type' => SuspensionType::SPECIFIED_ADJOURNMENT],
                3,
                ['Aanhouding'],
            ],
            'unspecified adjournment' => [PetitionEventType::UNSPECIFIED_ADJOURNMENT, [], 3, ['Aanhouding']],
            'appeal not timely' => [PetitionEventType::APPEAL_DECISION_NOT_TIMELY, ['duration' => 14], 2, ['BNT']],
            'adjournment letter' => [PetitionEventType::ADJOURNMENT, ['duration' => 42], 30, ['Verdaging']],
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function petitionWithEvent(PetitionEventType $type, array $attributes): Petition
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        PetitionEvent::factory()->recycle($petition)->withType($type)->create($attributes);

        $petition->refresh();

        return $petition;
    }

    /**
     * @return array<string>
     */
    private function collectFor(Petition $petition): array
    {
        return $this->app->make(PetitionParticularityCollector::class)->collectParticularities($petition);
    }
}
