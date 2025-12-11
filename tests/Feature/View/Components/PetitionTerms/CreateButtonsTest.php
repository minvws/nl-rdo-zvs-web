<?php

declare(strict_types=1);

namespace Tests\Feature\View\Components\PetitionTerms;

use App\Enums\Authorization\Permission;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use App\View\Components\Petition\PetitionTerms\CreateButtons;
use Illuminate\Contracts\View\View;
use Illuminate\Testing\TestComponent;
use Tests\Feature\FeatureTestCase;

class CreateButtonsTest extends FeatureTestCase
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

        $component = $this->component(CreateButtons::class, [
            'petitionTypeTypeConfig' => [
                $petition->petitionType->type->value => [
                    'petition_terms_enabled' => true,
                    'petition_terms' => [],
                ],
            ],
            'petition' => $petition,
        ]);

        $this->assertInstanceOf(TestComponent::class, $component);
    }

    public function testRenderReturnsViewWhenEnabled(): void
    {
        $petition = Petition::factory()->create();

        $component = new CreateButtons(
            [
                $petition->petitionType->type->value => [
                    'petition_terms_enabled' => true,
                    'petition_terms' => [],
                ],
            ],
            $petition,
        );

        $this->assertInstanceOf(View::class, $component->render());
    }

    public function testRenderWhenDisabled(): void
    {
        $petition = Petition::factory()->create();

        $component = $this->component(CreateButtons::class, [
            'petitionTypeTypeConfig' => [
                $petition->petitionType->type->value => [
                    'petition_terms_enabled' => false,
                    'petition_terms' => [],
                ],
            ],
            'petition' => $petition,
        ]);

        $this->assertInstanceOf(TestComponent::class, $component);
    }

    public function testRenderReturnsNullWhenDisabled(): void
    {
        $petition = Petition::factory()->create();

        $component = new CreateButtons(
            [
                $petition->petitionType->type->value => [
                    'petition_terms_enabled' => false,
                    'petition_terms' => [],
                ],
            ],
            $petition,
        );

        $this->assertNull($component->render());
    }
}
