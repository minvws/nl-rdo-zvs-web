<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Filter\FilterClearAction;
use App\Actions\Filter\FilterGetAction;
use App\Actions\Filter\FilterSaveAction;
use App\Enums\ArchiveFilter;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Collection;

use function array_merge;
use function collect;
use function in_array;
use function is_array;
use function is_string;

abstract readonly class AbstractFilterService
{
    public function __construct(
        private FilterGetAction $filterGetAction,
        private FilterSaveAction $filterSaveAction,
        private FilterClearAction $filterClearAction,
        private Redirector $redirector,
        private UrlGenerator $urlGenerator,
    ) {
    }

    public function handleFilterPersistence(Request $request, User $user, Department $department): ?RedirectResponse
    {
        if ($request->has('filter')) {
            $this->saveCurrentFilters($request, $user, $department);

            return null;
        }

        return $this->redirectToSavedFiltersIfExist($user, $department);
    }

    public function ensureArchiveFilterDefault(Request $request): void
    {
        if ($request->has('filter.archive')) {
            return;
        }

        $currentFilters = $this->getCurrentFiltersAsArray($request);

        $request->merge([
            'filter' => array_merge($currentFilters, [
                'archive' => ArchiveFilter::HIDE_ARCHIVED->value,
            ]),
        ]);
    }

    /**
     * Removes the value of the given filter from the request when it is not one of the available options, for example
     * because the last matching record changed. Otherwise the filter would show no results and could not be cleared,
     * because the selected value is missing from the options.
     *
     * @template TValue of string
     *
     * @param Collection<int, TValue> $availableValues
     */
    public function removeUnavailableFilterValue(Request $request, string $filter, Collection $availableValues): void
    {
        $currentFilters = $this->getCurrentFiltersAsArray($request);
        $value = $currentFilters[$filter] ?? null;

        if (in_array($value, ['', null], true)) {
            return;
        }

        if (is_string($value) && $availableValues->containsStrict($value)) {
            return;
        }

        unset($currentFilters[$filter]);

        $request->query->set('filter', $currentFilters);
    }

    abstract protected function getFilterContext(): string;

    abstract protected function getIndexRouteName(): RouteName;

    /**
     * @return array<mixed>
     */
    private function getCurrentFiltersAsArray(Request $request): array
    {
        $currentFilters = $request->input('filter', []);

        if (!is_array($currentFilters)) {
            return [];
        }

        return $currentFilters;
    }

    private function saveCurrentFilters(Request $request, User $user, Department $department): void
    {
        $filterValue = $request->input('filter', []);

        if ($this->shouldClearFilters($filterValue)) {
            $this->filterClearAction->execute($user, $department, $this->getFilterContext());

            return;
        }

        /** @var array<string, mixed> $urlFilters */
        $urlFilters = (array) $filterValue;

        /** @var array<string, mixed> $cleanedFilters */
        $cleanedFilters = collect($urlFilters)
            ->reject(static fn($value): bool => in_array($value, ['', null, 'null'], true))
            ->toArray();

        $this->filterSaveAction->execute($user, $department, $this->getFilterContext(), $cleanedFilters);
    }

    private function shouldClearFilters(mixed $filterValue): bool
    {
        return $filterValue === 'clear' || $filterValue === [];
    }

    private function redirectToSavedFiltersIfExist(User $user, Department $department): ?RedirectResponse
    {
        $savedFilters = $this->filterGetAction->execute($user, $department, $this->getFilterContext());

        if ($savedFilters === []) {
            return null;
        }

        return $this->redirector->to($this->urlGenerator->route($this->getIndexRouteName(), [
            'department' => $department,
            'filter' => $savedFilters,
        ]));
    }
}
