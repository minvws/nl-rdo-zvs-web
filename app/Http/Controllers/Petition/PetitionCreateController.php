<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Config\Config;
use App\Enums\Ability;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionCategory;
use App\Models\PetitionType;
use App\Services\Petition\PetitionNumberGeneratorInterface;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

use function sprintf;

final readonly class PetitionCreateController
{
    public function __construct(
        private Factory $view,
        private Gate $gate,
        private PetitionNumberGeneratorInterface $petitionNumberGenerator,
    ) {
    }

    public function __invoke(Department $department, PetitionType $petitionType): View
    {
        $this->gate->authorize(Ability::CREATE, Petition::class);
        $petitionTypeConfiguration = Config::array(sprintf('petition_type_type.%s.optional_form_fields', $petitionType->type->value));
        $nextPetitionNumber = $this->petitionNumberGenerator->suggestNextNumber($department);

        return $this->view->make('petition.create', [
            'petitionCategories' => PetitionCategory::query()
                ->where('department_id', $department->id)
                ->active()->get(),
            'petitionType' => $petitionType,
            'petitionTypeConfiguration' => $petitionTypeConfiguration,
            'department' => $department,
            'nextPetitionNumber' => $nextPetitionNumber,
        ]);
    }
}
