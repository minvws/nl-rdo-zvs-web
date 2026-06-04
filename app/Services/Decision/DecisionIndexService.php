<?php

declare(strict_types=1);

namespace App\Services\Decision;

use App\Actions\Filter\FilterGetAction;
use App\Enums\ArchiveFilter;
use App\Enums\DecisionType;
use App\Enums\ProcessingStepStatus;
use App\Http\Requests\SortHelper;
use App\Models\Builder\Decision\DecisionQueryBuilder;
use App\Models\Decision;
use App\Models\Department;
use App\Models\ProcessingStep;
use App\Models\Team;
use App\Models\User;
use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class DecisionIndexService
{
    public function __construct(
        #[Config('app.pagination.items_per_page')] private int $paginationItemsPerPage,
        private DecisionFilterService $filterService,
        private FilterGetAction $filterGetAction,
        private SortHelper $sortHelper,
    ) {
    }

    /**
     * @return array{
     *     decisions: Collection<int, Decision>,
     *     decisionCount: int,
     *     search: string,
     *     paginator: LengthAwarePaginator<int, Decision>,
     *     department: Department,
     *     hasSavedFilters: bool,
     *     archiveFilters: array<ArchiveFilter>,
     *     decisionTypes: array<DecisionType>,
     *     sortHelper: SortHelper,
     *     processingStepsInProgress: array<string>,
     *     usedTeams: Collection<int, Team>
     * }
     */
    public function getIndexData(Request $request, Department $department, User $user): array
    {
        $this->filterService->ensureArchiveFilterDefault($request);

        /** @var LengthAwarePaginator<int, Decision> $paginator */
        $paginator = DecisionQueryBuilder::make($request)
            ->where('department_id', $department->id)
            ->paginate($this->paginationItemsPerPage);

        return [
            'decisions' => $paginator->getCollection(),
            'decisionCount' => $paginator->total(),
            'search' => $request->query->getString('search'),
            'paginator' => $paginator->withQueryString(),
            'department' => $department,
            'hasSavedFilters' => $this->hasUserSavedFilters($user, $department),
            'archiveFilters' => ArchiveFilter::cases(),
            'decisionTypes' => DecisionType::cases(),
            'sortHelper' => $this->sortHelper,
            'processingStepsInProgress' => $this->getProcessingStepsInProgressForDepartment($department),
            'usedTeams' => $this->getUsedTeams($department),
        ];
    }

    /**
     * @return array<string>
     */
    private function getProcessingStepsInProgressForDepartment(Department $department): array
    {
        /** @var Collection<int, string> $collection */
        $collection = ProcessingStep::query()
            ->select('name')
            ->whereRelation('decision', 'department_id', $department->id)
            ->where('status', ProcessingStepStatus::PENDING)
            ->orderBy('name')
            ->distinct()
            ->pluck('name');

        return $collection
            ->map(static fn (mixed $value): string => $value)
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Team>
     */
    private function getUsedTeams(Department $department): Collection
    {
        return Team::query()
            ->where('department_id', $department->id)
            ->whereHas('decisions')
            ->active()
            ->orderBy('name')
            ->get();
    }

    private function hasUserSavedFilters(User $user, Department $department): bool
    {
        $savedFilters = $this->filterGetAction->execute($user, $department, 'decision');

        return $savedFilters !== [];
    }
}
