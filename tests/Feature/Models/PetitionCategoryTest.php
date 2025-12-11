<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionCategory;
use App\Models\PetitionType;
use Tests\Feature\FeatureTestCase;

class PetitionCategoryTest extends FeatureTestCase
{
    public function testPetitionsRelationship(): void
    {
        $department = Department::factory()->create();
        $petitionCategory = PetitionCategory::factory()->recycle($department)->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();

        $petition = Petition::factory()
            ->recycle($department)
            ->recycle($petitionType)
            ->create(['petition_category_id' => $petitionCategory->id]);

        $this->assertInstanceOf(Petition::class, $petition);
        $this->assertEquals($petitionCategory->id, $petition->petition_category_id);
        $this->assertEquals(1, $petitionCategory->petitions->count());
    }

    public function testCastsActiveAsBoolean(): void
    {
        $petitionCategory = PetitionCategory::factory()->create(['active' => true]);

        $this->assertIsBool($petitionCategory->active);
        $this->assertTrue($petitionCategory->active);

        $inactivePetitionCategory = PetitionCategory::factory()->create(['active' => false]);

        $this->assertIsBool($inactivePetitionCategory->active);
        $this->assertFalse($inactivePetitionCategory->active);
    }

    public function testActiveScope(): void
    {
        $department = Department::factory()->create();

        $activePetitionCategory = PetitionCategory::factory()
            ->recycle($department)
            ->create(['active' => true]);

        $inactivePetitionCategory = PetitionCategory::factory()
            ->recycle($department)
            ->create(['active' => false]);

        $activeCategories = PetitionCategory::query()
            ->where('department_id', $department->id)
            ->active()
            ->get();

        $this->assertCount(1, $activeCategories);
        $this->assertTrue($activeCategories->pluck('id')->contains($activePetitionCategory->id));
        $this->assertFalse($activeCategories->pluck('id')->contains($inactivePetitionCategory->id));
    }

    public function testDepartmentRelationship(): void
    {
        $department = Department::factory()->create();
        $petitionCategory = PetitionCategory::factory()->recycle($department)->create();

        $this->assertEquals($department->id, $petitionCategory->department->id);
        $this->assertEquals($department->name, $petitionCategory->department->name);
    }

    public function testHasFactory(): void
    {
        $petitionCategory = PetitionCategory::factory()->create();

        $this->assertNotNull($petitionCategory->id);
        $this->assertNotNull($petitionCategory->name);
        $this->assertNotNull($petitionCategory->department_id);
    }

    public function testFactoryCanCreateWithCustomAttributes(): void
    {
        $department = Department::factory()->create();
        $customName = 'Custom Category Name';

        $petitionCategory = PetitionCategory::factory()
            ->recycle($department)
            ->create([
                'name' => $customName,
                'active' => false,
            ]);

        $this->assertEquals($customName, $petitionCategory->name);
        $this->assertFalse($petitionCategory->active);
        $this->assertEquals($department->id, $petitionCategory->department_id);
    }
}
