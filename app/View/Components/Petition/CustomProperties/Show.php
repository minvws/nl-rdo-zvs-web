<?php

declare(strict_types=1);

namespace App\View\Components\Petition\CustomProperties;

use App\Factories\View\Petition\PetitionCustomPetitionPropertiesViewFactory;
use App\Models\Department;
use App\Models\Petition;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\View\Factory;
use Ramsey\Uuid\UuidInterface;
use Webmozart\Assert\Assert;

class Show extends Component
{
    public function __construct(
        public Petition $petition,
        public Department $department,
        private readonly Factory $view,
    ) {
    }

    public function render(): View
    {
        $viewBuilder = new PetitionCustomPetitionPropertiesViewFactory();
        $customPetitionProperties = $viewBuilder->build(
            $this->petition->availableCustomPetitionProperties,
            $this->petition->customPetitionProperties->pluck('id')
                ->map(static function (mixed $id): UuidInterface {
                    Assert::isInstanceOf($id, UuidInterface::class);

                    return $id;
                }),
        );

        return $this->view
            ->make('petition.custom_petition_property.show')
            ->with([
                'petition' => $this->petition,
                'customPetitionProperties' => $customPetitionProperties,
                'department' => $this->department,
            ]);
    }
}
