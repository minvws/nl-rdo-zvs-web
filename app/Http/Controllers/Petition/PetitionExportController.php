<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\PetitionExport\PetitionExportCreateAction;
use App\Actions\PetitionExport\PetitionExportDownloadAction;
use App\Enums\Ability;
use App\Http\Requests\Petition\PetitionExportRequest;
use App\Models\Department;
use App\Models\PetitionCategory;
use App\Models\PetitionExport;
use App\Models\PetitionType;
use App\Services\Petition\Export\PetitionExportException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Redirector;
use Illuminate\View\Factory;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function __;

final readonly class PetitionExportController
{
    public function __construct(
        private Factory $view,
        private Redirector $redirector,
    ) {
    }

    #[Authorize(Ability::VIEW_ANY, PetitionExport::class)]
    public function index(Request $request, Department $department): View
    {
        $petitionTypes = PetitionType::query()
            ->where('department_id', $department->id)
            ->orderBy('name')
            ->get();

        $usedPetitionTypes = PetitionType::query()
            ->where('department_id', $department->id)
            ->isInUse()
            ->orderBy('name')
            ->get();

        $petitionCategories = PetitionCategory::query()
            ->where('department_id', $department->id)
            ->orderBy('name')
            ->get();

        $exports = PetitionExport::query()
            ->where('department_id', $department->id)
            ->with(['petitionType', 'petitionCategory'])->latest()
            ->cursorPaginate(15);

        return $this->view->make('petition.exports.index', [
            'department' => $department,
            'petitionTypes' => $petitionTypes,
            'usedPetitionTypes' => $usedPetitionTypes,
            'petitionCategories' => $petitionCategories,
            'exports' => $exports,
        ]);
    }

    /**
     * @throws PetitionExportException
     */
    #[Authorize(Ability::CREATE, PetitionExport::class)]
    public function export(
        PetitionExportRequest $request,
        Department $department,
        PetitionExportCreateAction $action,
    ): RedirectResponse {
        $action->execute($department, $request->validated());

        return $this->redirector->back()
            ->with('message.success', __('petition.export_generated'));
    }

    #[Authorize(Ability::VIEW, 'petitionExport')]
    public function download(
        Department $department,
        PetitionExport $petitionExport,
        PetitionExportDownloadAction $action,
    ): StreamedResponse {
        return $action->execute($petitionExport);
    }

    #[Authorize(Ability::DELETE, 'petitionExport')]
    public function delete(Department $department, PetitionExport $petitionExport): RedirectResponse
    {
        $petitionExport->delete();

        return $this->redirector->back()
            ->with('message.success', __('general.deleted'));
    }
}
