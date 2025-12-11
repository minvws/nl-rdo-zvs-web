<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\PublicHoliday\CreatePublicHolidayAction;
use App\Actions\PublicHoliday\UpdatePublicHolidayAction;
use App\Enums\RouteName;
use App\Http\Requests\PublicHoliday\PublicHolidayStoreRequest;
use App\Http\Requests\PublicHoliday\PublicHolidayUpdateRequest;
use App\Models\PublicHoliday;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

use function __;

final readonly class PublicHolidayController
{
    public function __construct(
        private Redirector $redirector,
        private Factory $view,
        #[Config('app.pagination.items_per_page')]
        private int $paginationItemsPerPage,
    ) {
    }

    public function index(): View
    {
        $publicHolidays = PublicHoliday::query()->cursorPaginate($this->paginationItemsPerPage);

        return $this->view->make('public-holiday.index', ['publicHolidays' => $publicHolidays]);
    }

    public function create(): View
    {
        return $this->view->make('public-holiday.create');
    }

    public function store(PublicHolidayStoreRequest $publicHolidayStore, CreatePublicHolidayAction $action): RedirectResponse
    {
        $action->execute($publicHolidayStore->validated());

        return $this->redirector->route(RouteName::ADMIN_PUBLIC_HOLIDAY_INDEX)
            ->with('message.success', __('general.saved'));
    }

    public function edit(PublicHoliday $publicHoliday): View
    {
        return $this->view->make('public-holiday.edit', [
            'publicHoliday' => $publicHoliday,
        ]);
    }

    public function update(PublicHoliday $publicHoliday, PublicHolidayUpdateRequest $publicHolidayUpdate, UpdatePublicHolidayAction $action): RedirectResponse
    {
        $action->execute($publicHoliday, $publicHolidayUpdate->validated());

        return $this->redirector->route(RouteName::ADMIN_PUBLIC_HOLIDAY_EDIT, ['publicHoliday' => $publicHoliday->id])
            ->with('message.success', __('general.saved'));
    }

    public function delete(PublicHoliday $publicHoliday): RedirectResponse
    {
        $publicHoliday->delete();

        return $this->redirector->route(RouteName::ADMIN_PUBLIC_HOLIDAY_INDEX)
            ->with('message.success', __('general.deleted'));
    }
}
