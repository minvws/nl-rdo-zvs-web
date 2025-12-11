<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\Authorization\DepartmentRole;
use App\Models\Contact;
use App\Models\Decision;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionCategory;
use App\Models\PetitionExport;
use App\Models\PetitionType;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class DepartmentTest extends FeatureTestCase
{
    #[Test]
    public function testUsersRelationship(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();

        $department->users()->attach($user, ['role' => DepartmentRole::WRITE]);

        $this->assertEquals(1, $department->users->count());
        $this->assertInstanceOf(User::class, $department->users->first());
        $this->assertEquals(DepartmentRole::WRITE, $department->users->first()->pivot->role);
    }

    #[Test]
    public function testPetitionsRelationship(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();

        $petition = Petition::factory()
            ->recycle($department)
            ->recycle($petitionType)
            ->create();

        $this->assertInstanceOf(Petition::class, $petition);
        $this->assertEquals($department->id, $petition->department_id);
        $this->assertEquals(1, $department->petitions->count());
    }

    #[Test]
    public function testPetitionCategoriesRelationship(): void
    {
        $department = Department::factory()->create();

        $petitionCategory = PetitionCategory::factory()->recycle($department)->create();

        $this->assertInstanceOf(PetitionCategory::class, $petitionCategory);
        $this->assertEquals($department->id, $petitionCategory->department_id);
        $this->assertEquals(1, $department->petitionCategories->count());
    }

    #[Test]
    public function testPetitionTypesRelationship(): void
    {
        $department = Department::factory()->create();

        $petitionType = PetitionType::factory()->recycle($department)->create();

        $this->assertInstanceOf(PetitionType::class, $petitionType);
        $this->assertEquals($department->id, $petitionType->department_id);
        $this->assertEquals(1, $department->petitionTypes->count());
    }

    #[Test]
    public function testDecisionsRelationship(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create();

        $this->assertInstanceOf(Decision::class, $decision);
        $this->assertEquals($department->id, $decision->department_id);
        $this->assertEquals(1, $department->decisions->count());
    }

    #[Test]
    public function testContactsRelationship(): void
    {
        $department = Department::factory()->create();
        $contact = Contact::factory()->recycle($department)->create();

        $this->assertCount(1, $department->contacts);
        $this->assertInstanceOf(Contact::class, $department->contacts->first());
        $this->assertEquals($contact->last_name, $department->contacts->first()->last_name);
    }

    #[Test]
    public function testPetitionExportsRelationship(): void
    {
        $department = Department::factory()->create();

        $petitionExport = PetitionExport::factory()->recycle($department)->create();

        $department->refresh();

        $this->assertCount(1, $department->petitionExports);
        $this->assertInstanceOf(PetitionExport::class, $department->petitionExports->first());
        $this->assertEquals($petitionExport->type, $department->petitionExports->first()->type);
    }
}
