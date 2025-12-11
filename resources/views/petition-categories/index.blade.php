@use(App\Enums\Authorization\Permission)
@use(App\Enums\RouteName)

<x-app-layout>
    <x-slot name="header">
        <div class="action-bar">
            <h1>
                {{ __('petition_category.model_plural') }}
            </h1>
            @can(Permission::PETITION_CATEGORY_WRITE->value)
                <a
                    class="button"
                    href="{{ route(RouteName::DEPARTMENTS_ADMIN_PETITION_CATEGORIES_CREATE, ['department' => $department]) }}">
                    {{ __('petition_category.create') }}
                    <x-tabler-plus
                        aria-hidden="true"
                        focusable="false" />
                </a>
            @endcan
        </div>
    </x-slot>
    <section class="mt-5">
        <div class="visually-grouped">
            <table>
                <caption class="visually-hidden">
                    {{ __('petition_category.model_plural') }}
                </caption>
                <thead>
                    <tr>
                        <th scope="col">{{ __('petition_category.model_singular') }}</th>
                        <th scope="col">{{ __('petition_category.active') }}</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($petitionCategories as $petitionCategory)
                        <tr>
                            <th scope="row">{{ $petitionCategory->name }}</th>
                            <td>
                                @if ($petitionCategory->active)
                                    {{ __('general.yes') }}
                                @else
                                    {{ __('general.no') }}
                                @endif
                            </td>
                            <td>
                                <div class="actions">
                                    @can(Permission::PETITION_CATEGORY_WRITE->value)
                                        <a
                                            class="icon-only"
                                            href="{{ route(RouteName::DEPARTMENTS_ADMIN_PETITION_CATEGORIES_EDIT, ['department' => $department, 'petitionCategory' => $petitionCategory->id]) }}"
                                            aria-label="{{ __('general.view') }}">
                                            <x-tabler-chevron-right
                                                aria-hidden="true"
                                                focusable="false" />
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">{{ __('petition_category.no_categories') }}</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr></tr>
                </tfoot>
            </table>
            {{ $petitionCategories->links() }}
        </div>
    </section>
</x-app-layout>
