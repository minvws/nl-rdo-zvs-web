<?php

declare(strict_types=1);

namespace App\Services\Decision;

use App\Actions\Filter\FilterGetAction;
use App\Enums\ArchiveFilter;
use App\Enums\DecisionType;
use App\Http\Requests\SortHelper;
use App\Models\Builder\Decision\DecisionQueryBuilder;
use App\Models\Decision;
use App\Models\Department;
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
     *     sortHelper: SortHelper
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
        ];
    }

    private function hasUserSavedFilters(User $user, Department $department): bool
    {
        $savedFilters = $this->filterGetAction->execute($user, $department, 'decision');

        return $savedFilters !== [];
    }
}
