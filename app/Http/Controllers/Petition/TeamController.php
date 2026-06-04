<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\Team\CreateTeam;
use App\Actions\Team\UpdateTeam;
use App\Enums\RouteName;
use App\Http\Requests\Team\TeamCreateRequest;
use App\Http\Requests\Team\TeamUpdateRequest;
use App\Models\Department;
use App\Models\Team;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

use function __;

final readonly class TeamController
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
        $teams = Team::query()
            ->where('department_id', $department->id)
            ->paginate($this->paginationItemsPerPage);

        return $this->view->make('teams.index', [
            'teams' => $teams,
            'department' => $department,
        ]);
    }

    public function create(Department $department): View
    {
        return $this->view->make('teams.create', [
            'department' => $department,
        ]);
    }

    public function store(Department $department, TeamCreateRequest $teamCreateRequest, CreateTeam $createTeam): RedirectResponse
    {
        $createTeam->execute($department, $teamCreateRequest->validated());

        return $this->redirector->route(RouteName::DEPARTMENTS_ADMIN_TEAMS_INDEX, ['department' => $department])
            ->with('message.success', __('general.saved'));
    }

    public function edit(Department $department, Team $team): View
    {
        return $this->view->make('teams.edit', [
            'team' => $team,
            'department' => $department,
        ]);
    }

    public function update(Department $department, Team $team, TeamUpdateRequest $teamUpdateRequest, UpdateTeam $updateTeam): RedirectResponse
    {
        $updateTeam->execute($team, $teamUpdateRequest->validated());

        return $this->redirector->route(RouteName::DEPARTMENTS_ADMIN_TEAMS_INDEX, ['department' => $department])
            ->with('message.success', __('general.saved'));
    }
}
