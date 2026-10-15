<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\Tag\CreateTagAction;
use App\Actions\Tag\DeleteTagAction;
use App\Actions\Tag\UpdateTagAction;
use App\Enums\RouteName;
use App\Http\Requests\Tag\TagCreateRequest;
use App\Http\Requests\Tag\TagUpdateRequest;
use App\Models\Department;
use App\Models\Tag;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Throwable;

use function __;

final readonly class TagController
{
    public function __construct(
        private Redirector $redirector,
        private Factory $view,
        #[Config('app.pagination.items_per_page')]
        private int $paginationItemsPerPage,
    ) {
    }

    public function index(Department $department): View
    {
        $tags = Tag::query()
            ->forDepartment($department)
            ->withCount('petitions')
            ->paginate($this->paginationItemsPerPage);

        return $this->view->make('tags.index', [
            'tags' => $tags,
            'department' => $department,
        ]);
    }

    public function create(Department $department): View
    {
        return $this->view->make('tags.create', [
            'department' => $department,
        ]);
    }

    public function store(Department $department, TagCreateRequest $tagCreateRequest, CreateTagAction $createTagAction): RedirectResponse
    {
        $createTagAction->execute($department, $tagCreateRequest->validated());

        return $this->redirector->route(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department])
            ->with('message.success', __('general.saved'));
    }

    public function edit(Department $department, Tag $tag): View
    {
        return $this->view->make('tags.edit', [
            'tag' => $tag,
            'department' => $department,
        ]);
    }

    public function update(Department $department, Tag $tag, TagUpdateRequest $tagUpdateRequest, UpdateTagAction $updateTagAction): RedirectResponse
    {
        $updateTagAction->execute($tag, $tagUpdateRequest->validated());

        return $this->redirector->route(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department])
            ->with('message.success', __('general.saved'));
    }

    /**
     * @throws Throwable
     */
    public function delete(Department $department, Tag $tag, DeleteTagAction $deleteTagAction): RedirectResponse
    {
        $deleteTagAction->execute($tag);

        return $this->redirector->route(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department])
            ->with('message.success', __('general.deleted'));
    }
}
