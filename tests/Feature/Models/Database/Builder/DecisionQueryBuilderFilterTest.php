<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Database\Builder;

use App\Enums\DecisionCriteria;
use App\Models\Builder\Decision\DecisionQueryBuilder;
use App\Models\Decision;
use App\Models\Department;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class DecisionQueryBuilderFilterTest extends FeatureTestCase
{
    public function testEmpty(): void
    {
        $decisionQueryBuilder = DecisionQueryBuilder::make();
        $this->assertEquals(0, $decisionQueryBuilder->count());
    }

    public function testWithoutFilters(): void
    {
        $department = Department::factory()->create();
        $count = $this->faker->numberBetween(3, 5);
        Decision::factory()
            ->recycle($department)
            ->count($count)
            ->create();

        $this->assertEquals($count, DecisionQueryBuilder::make()->count());
    }

    #[Test]
    public function testSearchInNameField(): void
    {
        $department = Department::factory()->create();
        $searchTerm = 'xxxxx';
        $request = new Request([
            'filter' => [
                DecisionCriteria::SEARCH->value => $searchTerm,
            ],
        ]);

        Decision::factory()->recycle($department)->create([
            'name' => $searchTerm . ' ' . $this->faker->unique()->word(),
        ]);
        Decision::factory()->recycle($department)->create([
            'name' => $this->faker->unique()->words(2, true),
        ]);

        $this->assertEquals(1, DecisionQueryBuilder::make($request)->count());
    }

    #[Test]
    public function testSearchInReferenceField(): void
    {
        $department = Department::factory()->create();
        $searchTerm = Str::random(5); // Random search term to ensure uniqueness
        $request = new Request([
            'filter' => [
                DecisionCriteria::SEARCH->value => $searchTerm,
            ],
        ]);

        Decision::factory()->recycle($department)->create([
            'name' => $this->faker->unique()->words(2, true),
            'reference' => $this->faker->unique()->word() . '-' . $searchTerm,
        ]);
        Decision::factory()->recycle($department)->create([
            'name' => $this->faker->unique()->words(2, true),
            'reference' => $this->faker->unique()->word() . '-' . $this->faker->unique()->word(),
        ]);

        $this->assertEquals(1, DecisionQueryBuilder::make($request)->count());
    }

    #[Test]
    public function testSearchInReviewbatchField(): void
    {
        $department = Department::factory()->create();
        $searchTerm = Str::random(5); // Random search term to ensure uniqueness
        $request = new Request([
            'filter' => [
                DecisionCriteria::SEARCH->value => $searchTerm,
            ],
        ]);

        Decision::factory()->recycle($department)->create([
            'name' => $this->faker->unique()->words(2, true),
            'reviewbatch' => $searchTerm . '-' . $this->faker->unique()->word(),
        ]);
        Decision::factory()->recycle($department)->create([
            'name' => $this->faker->unique()->words(2, true),
            'reviewbatch' => $this->faker->unique()->word() . '-' . $this->faker->unique()->word(),
        ]);

        $this->assertEquals(1, DecisionQueryBuilder::make($request)->count());
    }

    #[Test]
    public function testSearchInBothNameAndReferenceFields(): void
    {
        $department = Department::factory()->create();
        $searchTerm = Str::random(5); // Random search term to ensure uniqueness
        $request = new Request([
            'filter' => [
                DecisionCriteria::SEARCH->value => $searchTerm,
            ],
        ]);

        Decision::factory()->recycle($department)->create([
            'name' => $searchTerm . ' ' . $this->faker->unique()->word(),
            'reference' => $this->faker->unique()->word() . '-' . $this->faker->unique()->word(),
        ]);
        Decision::factory()->recycle($department)->create([
            'name' => $this->faker->unique()->words(2, true),
            'reference' => $this->faker->unique()->word() . '-' . $searchTerm,
        ]);

        $this->assertEquals(2, DecisionQueryBuilder::make($request)->count());
    }

    #[Test]
    public function testSearchWithMultipleTerms(): void
    {
        $department = Department::factory()->create();
        $searchTermA = Str::random(10);
        $searchTermB = Str::random(10);
        $request = new Request([
            'filter' => [
                DecisionCriteria::SEARCH->value => $searchTermA . ' ' . $searchTermB,
            ],
        ]);

        Decision::factory()->recycle($department)->create([
            'name' => $searchTermA . ' ' . $searchTermB . ' ' . $this->faker->unique()->word(),
        ]);
        Decision::factory()->recycle($department)->create([
            'name' => $searchTermA . ' ' . $this->faker->unique()->word(),
            'reference' => $this->faker->unique()->word() . '-' . $searchTermB,
        ]);
        Decision::factory()->recycle($department)->create([
            'name' => $searchTermA . ' ' . $this->faker->unique()->word(),
        ]);

        $this->assertEquals(2, DecisionQueryBuilder::make($request)->count());
    }

    #[Test]
    public function testSearchIsCaseInsensitive(): void
    {
        $department = Department::factory()->create();
        $searchTerm = Str::of(Str::random(5))->lower(); // Random search term to ensure uniqueness
        $request = new Request([
            'filter' => [
                DecisionCriteria::SEARCH->value => $searchTerm->upper()->toString(),
            ],
        ]);

        Decision::factory()->recycle($department)->create([
            'name' => $searchTerm->lower() . ' ' . $this->faker->unique()->word(),
        ]);
        Decision::factory()->recycle($department)->create([
            'reference' => $this->faker->unique()->word() . '-' . $searchTerm->lower(),
        ]);

        $this->assertEquals(2, DecisionQueryBuilder::make($request)->count());
    }

    #[Test]
    public function testTeamFilter(): void
    {
        $department = Department::factory()->create();
        $team = Team::factory()->for($department)->create();
        $filterDecision = Decision::factory()->for($team, 'team')->for($department)->create();

        $this->assertSingleFilterResult(DecisionCriteria::TEAM, $filterDecision->team->id->toString());
    }

    #[Test]
    public function testTeamFilterDoesNotMatchOtherDecisions(): void
    {
        $department = Department::factory()->create();
        $team = Team::factory()->for($department)->create();
        Decision::factory()->for($team, 'team')->for($department)->create();
        Decision::factory()->for($department)->count(2)->create();

        $request = new Request([
            'filter' => [
                DecisionCriteria::TEAM->value => $team->id->toString(),
            ],
        ]);

        $this->assertEquals(1, DecisionQueryBuilder::make($request)->count());
    }

    /**
     * @param array<string, mixed> $decisionAttributes
     */
    private function assertSingleFilterResult(DecisionCriteria $decisionCriteria, string $value, array $decisionAttributes = []): void
    {
        $department = Department::factory()->create();

        Decision::factory()
            ->for($department)
            ->count($this->faker->numberBetween(1, 3))
            ->create($decisionAttributes);

        $request = new Request([
            'filter' => [
                $decisionCriteria->value => $value,
            ],
        ]);

        $this->assertEquals(1, DecisionQueryBuilder::make($request)->count());
    }
}
