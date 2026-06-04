<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Config\Config;
use App\Enums\Ability;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionCategory;
use App\Models\PetitionType;
use App\Models\Team;
use App\Services\Petition\PetitionNumberGeneratorInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Attributes\Controllers\Authorize;

use function sprintf;

final readonly class PetitionCreateController
{
    public function __construct(
        private Factory $view,
        private PetitionNumberGeneratorInterface $petitionNumberGenerator,
    ) {
    }

    #[Authorize(Ability::CREATE, Petition::class)]
    public function __invoke(Department $department, PetitionType $petitionType): View
    {
        $petitionTypeConfiguration = Config::array(sprintf('petition_variant.%s.optional_form_fields', $petitionType->type->value));
        $nextPetitionNumber = $this->petitionNumberGenerator->suggestNextNumber($department);

        return $this->view->make('petition.create', [
            'petitionCategories' => PetitionCategory::query()
                ->where('department_id', $department->id)
                ->active()->get(),
            'teams' => Team::query()
                ->where('department_id', $department->id)
                ->active()
                ->orderBy('name')
                ->get(),
            'petitionType' => $petitionType,
            'petitionTypeConfiguration' => $petitionTypeConfiguration,
            'department' => $department,
            'nextPetitionNumber' => $nextPetitionNumber,
        ]);
    }
}
