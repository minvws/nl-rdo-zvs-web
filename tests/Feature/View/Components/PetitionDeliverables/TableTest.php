<?php

declare(strict_types=1);

namespace Tests\Feature\View\Components\PetitionDeliverables;

use App\Config\Config;
use App\Enums\Authorization\Permission;
use App\Enums\PetitionVariant;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionType;
use App\Models\User;
use App\View\Components\Petition\PetitionDeliverables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Testing\TestComponent;
use Tests\Feature\FeatureTestCase;

class TableTest extends FeatureTestCase
{
    public function testRenderWhenEnabled(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $this->setActiveDepartment($petition->department);
        $user = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($user);

        $component = $this->component(Table::class, [
            'petition' => $petition,
            'terms' => [],
            'petitionVariantConfig' => [
                $petition->petitionType->type->value => [
                    'petition_deliverables_enabled' => true,
                ],
            ],
        ]);

        $this->assertInstanceOf(TestComponent::class, $component);
    }

    public function testRenderReturnsViewWhenEnabled(): void
    {
        $petition = Petition::factory()->create();

        $component = new Table(
            [
                $petition->petitionType->type->value => [
                    'petition_deliverables_enabled' => true,
                ],
            ],
            $petition,
        );

        $this->assertInstanceOf(View::class, $component->render());
    }

    public function testRenderWhenDisabled(): void
    {
        $petition = Petition::factory()->create();

        $component = $this->component(Table::class, [
            'petition' => $petition,
            'terms' => [],
            'petitionVariantConfig' => [
                $petition->petitionType->type->value => [
                    'petition_deliverables_enabled' => false,
                ],
            ],
        ]);

        $this->assertInstanceOf(TestComponent::class, $component);
    }

    public function testRenderReturnsNullWhenDisabled(): void
    {
        $petition = Petition::factory()->create();

        $component = new Table(
            [
                $petition->petitionType->type->value => [
                    'petition_deliverables_enabled' => false,
                ],
            ],
            $petition,
        );

        $this->assertNull($component->render());
    }

    public function testVerzetShowsNoDeadlines(): void
    {
        $petitionType = PetitionType::factory()->create(['type' => PetitionVariant::VERZET]);
        $petition = Petition::factory()->for($petitionType)->create();

        $component = new Table(Config::array('petition_variant'), $petition);

        $this->assertNull($component->render());
    }

    public function testCivieleProcedureShowsNoDeadlines(): void
    {
        $petitionType = PetitionType::factory()->create(['type' => PetitionVariant::CIVIELE_PROCEDURE]);
        $petition = Petition::factory()->for($petitionType)->create();

        $component = new Table(Config::array('petition_variant'), $petition);

        $this->assertNull($component->render());
    }
}
