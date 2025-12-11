<?php

declare(strict_types=1);

namespace Tests\Feature\View\Components\PetitionDeliverables;

use App\Enums\Authorization\Permission;
use App\Models\Department;
use App\Models\Petition;
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
            'petitionTypeTypeConfig' => [
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
            'petitionTypeTypeConfig' => [
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
}
