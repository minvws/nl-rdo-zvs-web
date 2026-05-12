<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Database\Builder\Decision;

use App\Enums\DecisionCriteria;
use App\Enums\ProcessingStepStatus;
use App\Models\Builder\Decision\DecisionQueryBuilder;
use App\Models\Decision;
use App\Models\Department;
use App\Models\ProcessingStep;
use App\ValueObjects\CalendarDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class DecisionQueryBuilderTest extends FeatureTestCase
{
    use DatabaseTransactions;

    #[Test]
    public function makesQueryBuilderWithDecisionModel(): void
    {
        $builder = DecisionQueryBuilder::make();

        $this->assertInstanceOf(Builder::class, $builder);
        $this->assertEquals(Decision::class, $builder->getModel()::class);
    }

    #[Test]
    public function sortsByNameAscending(): void
    {
        $department = Department::factory()->create();
        Decision::factory()->recycle($department)->create(['name' => 'Zebra Decision']);
        Decision::factory()->recycle($department)->create(['name' => 'Alpha Decision']);

        $request = new Request(['sort' => DecisionCriteria::NAME->value]);
        $results = DecisionQueryBuilder::make($request)->get();

        $this->assertEquals('Alpha Decision', $results->first()->name);
    }

    #[Test]
    public function sortsByNameDescending(): void
    {
        $department = Department::factory()->create();
        Decision::factory()->recycle($department)->create(['name' => 'Alpha Decision']);
        Decision::factory()->recycle($department)->create(['name' => 'Zebra Decision']);

        $request = new Request(['sort' => '-' . DecisionCriteria::NAME->value]);
        $results = DecisionQueryBuilder::make($request)->get();

        $this->assertEquals('Zebra Decision', $results->first()->name);
    }

    #[Test]
    public function sortsByReferenceAscending(): void
    {
        $department = Department::factory()->create();
        Decision::factory()->recycle($department)->create([
            'name' => 'Decision C',
            'reference' => 'REF-2025-003',
        ]);
        Decision::factory()->recycle($department)->create([
            'name' => 'Decision A',
            'reference' => 'REF-2025-001',
        ]);

        $request = new Request(['sort' => DecisionCriteria::REFERENCE->value]);
        $results = DecisionQueryBuilder::make($request)->get();

        $this->assertEquals('ref-2025-001', $results->first()->reference);
    }

    #[Test]
    public function defaultSortsById(): void
    {
        $department = Department::factory()->create();
        Decision::factory()->recycle($department)->create();
        $decision2 = Decision::factory()->recycle($department)->create();

        $results = DecisionQueryBuilder::make()->get();

        $this->assertEquals($decision2->id, $results->first()->id);
    }

    #[Test]
    public function paginatesResults(): void
    {
        $department = Department::factory()->create();
        Decision::factory()->count(25)->recycle($department)->create();

        $paginator = DecisionQueryBuilder::make()->paginate(10);

        $this->assertEquals(10, $paginator->count());
        $this->assertEquals(25, $paginator->total());
        $this->assertEquals(3, $paginator->lastPage());
    }

    #[Test]
    public function sortsByProgressAscending(): void
    {
        $department = Department::factory()->create();

        $partialDecision = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->unique()->word(),
        ]);
        ProcessingStep::factory()->count(2)->recycle($partialDecision)->create([
            'status' => ProcessingStepStatus::CLOSED,
        ]);
        ProcessingStep::factory()->recycle($partialDecision)->create([
            'status' => ProcessingStepStatus::PENDING,
        ]);

        $fullDecision = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->unique()->word(),
        ]);
        ProcessingStep::factory()->count(3)->recycle($fullDecision)->create([
            'status' => ProcessingStepStatus::CLOSED,
        ]);

        $lowDecision = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->unique()->word(),
        ]);
        ProcessingStep::factory()->recycle($lowDecision)->create([
            'status' => ProcessingStepStatus::CLOSED,
        ]);
        ProcessingStep::factory()->count(2)->recycle($lowDecision)->create([
            'status' => ProcessingStepStatus::PENDING,
        ]);

        $request = new Request(['sort' => DecisionCriteria::PROGRESS->value]);
        $results = DecisionQueryBuilder::make($request)->get();

        $this->assertEquals($lowDecision->id, $results->get(0)->id);
        $this->assertEquals($partialDecision->id, $results->get(1)->id);
        $this->assertEquals($fullDecision->id, $results->get(2)->id);
    }

    #[Test]
    public function sortsByProgressDescending(): void
    {
        $department = Department::factory()->create();

        $fullDecision5 = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->unique()->word(),
        ]);
        ProcessingStep::factory()->count(5)->recycle($fullDecision5)->create([
            'status' => ProcessingStepStatus::CLOSED,
        ]);

        $fullDecision3 = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->unique()->word(),
        ]);
        ProcessingStep::factory()->count(3)->recycle($fullDecision3)->create([
            'status' => ProcessingStepStatus::CLOSED,
        ]);

        $halfDecision = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->unique()->word(),
        ]);
        ProcessingStep::factory()->count(2)->recycle($halfDecision)->create([
            'status' => ProcessingStepStatus::CLOSED,
        ]);
        ProcessingStep::factory()->count(2)->recycle($halfDecision)->create([
            'status' => ProcessingStepStatus::PENDING,
        ]);

        $request = new Request(['sort' => '-' . DecisionCriteria::PROGRESS->value]);
        $results = DecisionQueryBuilder::make($request)->get();

        $this->assertEquals($fullDecision5->id, $results->get(0)->id);
        $this->assertEquals($fullDecision3->id, $results->get(1)->id);
        $this->assertEquals($halfDecision->id, $results->get(2)->id);
    }

    #[Test]
    public function sortsByDeadlineAscending(): void
    {
        $department = Department::factory()->create();

        $nearDeadlineDecision = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->unique()->word(),
        ]);
        ProcessingStep::factory()->recycle($nearDeadlineDecision)->create([
            'deadline_at' => CalendarDate::today()->addDays(1),
        ]);

        $farDeadlineDecision = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->unique()->word(),
        ]);
        ProcessingStep::factory()->recycle($farDeadlineDecision)->create([
            'deadline_at' => CalendarDate::today()->addDays(7),
        ]);

        $noDeadlineDecision = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->unique()->word(),
        ]);

        $request = new Request(['sort' => DecisionCriteria::DEADLINE->value]);
        $results = DecisionQueryBuilder::make($request)->get();

        $this->assertEquals($nearDeadlineDecision->id, $results->get(0)->id);
        $this->assertEquals($farDeadlineDecision->id, $results->get(1)->id);
        $this->assertEquals($noDeadlineDecision->id, $results->get(2)->id);
    }

    #[Test]
    public function sortsByDeadlineDescending(): void
    {
        $department = Department::factory()->create();

        $nearDeadlineDecision = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->unique()->word(),
        ]);
        ProcessingStep::factory()->recycle($nearDeadlineDecision)->create([
            'deadline_at' => CalendarDate::today()->addDays(1),
        ]);

        $farDeadlineDecision = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->unique()->word(),
        ]);
        ProcessingStep::factory()->recycle($farDeadlineDecision)->create([
            'deadline_at' => CalendarDate::today()->addDays(7),
        ]);

        $pastDeadlineDecision = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->unique()->word(),
        ]);
        ProcessingStep::factory()->recycle($pastDeadlineDecision)->create([
            'deadline_at' => CalendarDate::today()->subDays(1),
        ]);

        $request = new Request(['sort' => '-' . DecisionCriteria::DEADLINE->value]);
        $results = DecisionQueryBuilder::make($request)->get();

        $this->assertEquals($pastDeadlineDecision->id, $results->get(0)->id);
        $this->assertEquals($farDeadlineDecision->id, $results->get(1)->id);
        $this->assertEquals($nearDeadlineDecision->id, $results->get(2)->id);
    }
}
