<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\Petition\PetitionMessageUpdateAction;
use App\Enums\Ability;
use App\Enums\RouteName;
use App\Enums\WordTemplateId;
use App\Http\NotFoundException;
use App\Http\Requests\Petition\PetitionMessageUpdateRequest;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use App\Services\Petition\WordTemplate\WordTemplateException;
use App\Services\Petition\WordTemplate\WordTemplateProcessingService;
use App\Services\Petition\WordTemplate\WordTemplateProcessorException;
use App\Services\Petition\WordTemplate\WordTemplateReplacementsMapper;
use App\Services\Petition\WordTemplate\WordTemplateService;
use App\Services\Petition\WordTemplate\WordTemplateViewFactory;
use App\View\HtmxHelper;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Redirector;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use function __;
use function collect;

final readonly class PetitionCorrespondenceController
{
    public function __construct(
        private HtmxHelper $htmxHelper,
        private Factory $view,
        private Redirector $redirector,
        private ResponseFactory $responseFactory,
        private WordTemplateService $wordTemplateService,
        private WordTemplateProcessingService $wordTemplateProcessingService,
        private WordTemplateReplacementsMapper $wordTemplateReplacementsMapper,
        private WordTemplateViewFactory $wordTemplateViewFactory,
    ) {
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function index(Department $department, Petition $petition): View
    {
        $wordTemplates = $this->wordTemplateViewFactory->buildForDepartment($department->config_key);

        return $this->view->make('petition.correspondence.index', [
            'petition' => $petition,
            'wordTemplates' => $wordTemplates,
        ]);
    }

    #[Authorize(Ability::VIEW, 'petition')]
    public function download(Department $department, Petition $petition, WordTemplateId $wordTemplateId): BinaryFileResponse
    {
        $allowedTemplates = $this->wordTemplateViewFactory->buildForDepartment($department->config_key);
        $isAllowed = collect($allowedTemplates)->contains('word_template_id', $wordTemplateId->value);

        if (!$isAllowed) {
            throw new NotFoundException('Word template not available for this department');
        }

        try {
            $wordTemplate = $this->wordTemplateService->get($wordTemplateId);
            $path = $this->wordTemplateProcessingService->process($wordTemplate, $this->wordTemplateReplacementsMapper->map($petition));

            return $this->responseFactory->download($path, $wordTemplate->filename);
        } catch (WordTemplateException | WordTemplateProcessorException $exception) {
            throw NotFoundException::fromThrowable('Unable to load or process the word template', $exception);
        }
    }

    #[Authorize(Ability::VIEW, 'petition')]
    public function show(Department $department, Petition $petition): View
    {
        return $this->view->make('petition.correspondence.show', [
            'petition' => $petition,
            'department' => $department,
        ]);
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function edit(Request $request, Department $department, Petition $petition): Response
    {
        return $this->htmxHelper->makeFormViewResponse($request, 'petition.correspondence.edit', [
            'petition' => $petition,
            'department' => $department,
        ]);
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function update(
        Request $request,
        Department $department,
        Petition $petition,
        PetitionMessageUpdateRequest $messageUpdateRequest,
        PetitionMessageUpdateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse|View {
        $action->execute($petition, $messageUpdateRequest->validated(), $user);

        $petition->refresh();

        if ($this->htmxHelper->isHtmxRequest($request)) {
            return $this->show($department, $petition);
        }

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ])
            ->with('message.success', __('general.saved'));
    }
}
