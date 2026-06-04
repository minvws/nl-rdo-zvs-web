<?php

declare(strict_types=1);

namespace App\Services\Petition;

use App\Actions\Filter\FilterGetAction;
use App\Collections\PetitionCategoryCollection;
use App\Collections\PetitionStatusCollection;
use App\Collections\PetitionTypeCollection;
use App\Collections\PolicyDepartmentCollection;
use App\Collections\TeamCollection;
use App\Collections\UserCollection;
use App\Enums\ArchiveFilter;
use App\Models\Builder\Petition\PetitionQueryBuilder;
use App\Models\CustomPetitionProperty;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionCategory;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use App\Models\PolicyDepartment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class PetitionIndexService
{
    public function __construct(
        #[Config('app.pagination.items_per_page')]
        private int $paginationItemsPerPage,
        private PetitionFilterService $filterService,
        private FilterGetAction $filterGetAction,
    ) {
    }

    /**
     * @return array{
     *     petitions: Collection<int, Petition>,
     *     petitionCount: int,
     *     search: string,
     *     paginator: LengthAwarePaginator<int, Petition>,
     *     assignedUsers: UserCollection,
     *     petitionTypes: PetitionTypeCollection,
     *     policyDepartments: PolicyDepartmentCollection,
     *     usedPetitionCategories: PetitionCategoryCollection,
     *     usedPetitionTypes: PetitionTypeCollection,
     *     usedPetitionStatuses: PetitionStatusCollection,
     *     usedCustomProperties: Collection<int, CustomPetitionProperty>,
     *     usedTeams: TeamCollection,
     *     department: Department,
     *     hasSavedFilters: bool,
     *     archiveFilters: array<ArchiveFilter>
     * }
     */
    public function getIndexData(Request $request, Department $department, User $user): array
    {
        $this->filterService->ensureArchiveFilterDefault($request);

        /** @var LengthAwarePaginator<int, Petition> $paginator */
        $paginator = PetitionQueryBuilder::make($request)
            ->whereDepartment($department)
            ->withSumOfPenaltiesPerDate()
            ->withPenaltyToDate()
            ->paginate($this->paginationItemsPerPage);


        return [
            'petitions' => $paginator->getCollection(),
            'petitionCount' => $paginator->total(),
            'search' => $request->query->getString('search'),
            'paginator' => $paginator->withQueryString(),
            'assignedUsers' => User::isAssignee($department)->get(['*']),
            'petitionTypes' => $this->getActivePetitionTypes($department),
            'policyDepartments' => $this->getPolicyDepartments($department),
            'usedPetitionCategories' => $this->getUsedPetitionCategories($department),
            'usedPetitionTypes' => $this->getUsedPetitionTypes($department),
            'usedPetitionStatuses' => $this->getUsedPetitionStatuses($department),
            'usedCustomProperties' => $this->getUsedCustomProperties($department),
            'usedTeams' => $this->getUsedTeams($department),
            'department' => $department,
            'hasSavedFilters' => $this->hasUserSavedFilters($user, $department),
            'archiveFilters' => ArchiveFilter::cases(),
        ];
    }

    public function getUsedPetitionCategories(Department $department): PetitionCategoryCollection
    {
        return PetitionCategory::query()
            ->where('department_id', $department->id)
            ->whereHas('petitions')
            ->get();
    }

    public function getActivePetitionTypes(Department $department): PetitionTypeCollection
    {
        return PetitionType::query()->where('department_id', $department->id)->active()->get();
    }

    public function getUsedPetitionTypes(Department $department): PetitionTypeCollection
    {
        return PetitionType::query()
            ->where('department_id', $department->id)
            ->isInUse()
            ->orderBy('name')
            ->get();
    }

    private function getPolicyDepartments(Department $department): PolicyDepartmentCollection
    {
        return PolicyDepartment::query()
            ->whereHas('petitions', static function ($query) use ($department): void {
                $query->where('department_id', $department->id);
            })
            ->orderBy('name')
            ->get();
    }

    private function getUsedTeams(Department $department): TeamCollection
    {
        return Team::query()
            ->where('department_id', $department->id)
            ->whereHas('petitions')
            ->active()
            ->orderBy('name')
            ->get();
    }

    private function getUsedPetitionStatuses(Department $department): PetitionStatusCollection
    {
        return PetitionStatus::query()
            ->usedByDepartment($department)
            ->get();
    }

    /**
     * @return Collection<int, CustomPetitionProperty>
     */
    private function getUsedCustomProperties(Department $department): Collection
    {
        return CustomPetitionProperty::query()
            ->whereHas('petitions', static function ($query) use ($department): void {
                $query->where('department_id', $department->id);
            })
            ->orderBy('name')
            ->get();
    }

    private function hasUserSavedFilters(User $user, Department $department): bool
    {
        $savedFilters = $this->filterGetAction->execute($user, $department, 'petition');

        return $savedFilters !== [];
    }
}
