<?php

declare(strict_types=1);

namespace Tests\Feature\QueryBuilders;

use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionCategory;
use App\Models\PetitionType;
use App\QueryBuilders\PetitionCategoryQueryBuilder;
use ReflectionClass;
use Tests\Feature\FeatureTestCase;

class PetitionCategoryQueryBuilderTest extends FeatureTestCase
{
    public function testActiveScope(): void
    {
        $department = Department::factory()->create();

        $activeCategory = PetitionCategory::factory()
            ->recycle($department)
            ->create(['active' => true]);

        $inactiveCategory = PetitionCategory::factory()
            ->recycle($department)
            ->create(['active' => false]);

        $result = PetitionCategory::query()->active()->get();

        $this->assertTrue($result->contains('id', $activeCategory->id));
        $this->assertFalse($result->contains('id', $inactiveCategory->id));
    }

    public function testIsInUseMethodWithPetitions(): void
    {
        $department = Department::factory()->create();

        $categoryWithPetitions = PetitionCategory::factory()
            ->recycle($department)
            ->create();

        $categoryWithoutPetitions = PetitionCategory::factory()
            ->recycle($department)
            ->create();

        $petitionType = PetitionType::factory()
            ->recycle($department)
            ->create();

        // Create a petition linked to the first category
        Petition::factory()
            ->recycle($department)
            ->recycle($petitionType)
            ->create([
                'petition_category_id' => $categoryWithPetitions->id,
            ]);

        /** @var PetitionCategoryQueryBuilder $queryBuilder */
        $queryBuilder = PetitionCategory::query();

        // Use reflection to test the protected isInUse method
        $reflection = new ReflectionClass($queryBuilder);
        $isInUseMethod = $reflection->getMethod('isInUse');
        $isInUseMethod->setAccessible(true);

        // Test that category with petitions is considered in use
        $resultWithPetitions = $isInUseMethod->invoke($queryBuilder);
        $this->assertTrue($resultWithPetitions->where('id', $categoryWithPetitions->id)->exists());

        // Test that category without petitions is not considered in use
        $this->assertFalse($resultWithPetitions->where('id', $categoryWithoutPetitions->id)->exists());
    }

    public function testQueryBuilderReturnsCorrectInstance(): void
    {
        $queryBuilder = PetitionCategory::query();

        $this->assertInstanceOf(PetitionCategoryQueryBuilder::class, $queryBuilder);
    }

    public function testActiveScopeCanBeChained(): void
    {
        $department = Department::factory()->create();

        $activeCategory = PetitionCategory::factory()
            ->recycle($department)
            ->create([
                'active' => true,
                'name' => 'Test Active Category',
            ]);

        $inactiveCategory = PetitionCategory::factory()
            ->recycle($department)
            ->create([
                'active' => false,
                'name' => 'Test Inactive Category',
            ]);

        $result = PetitionCategory::query()
            ->active()
            ->where('name', 'LIKE', '%Test%')
            ->get();

        $this->assertTrue($result->contains('id', $activeCategory->id));
        $this->assertFalse($result->contains('id', $inactiveCategory->id));
        $this->assertCount(1, $result);
    }
}
