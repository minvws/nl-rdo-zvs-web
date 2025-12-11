@use(App\Enums\Ability)
@use(App\Enums\ExportType)
@use(App\Enums\RouteName)
@use(App\Facades\DisplayDate)
@use(App\Facades\Form)

@section('pageTitle', __('exports.export_overview'))

<x-app-layout>
    <x-slot name="header">
        <div class="action-bar">
            <h1>{{ __('exports.export') }}</h1>
        </div>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped filters">
            <form
                method="post"
                class="filters__form"
                action="{{ route(RouteName::DEPARTMENTS_PETITIONS_EXPORTS_EXPORT, ['department' => $department]) }}">
                @csrf
                <div class="form-filter-group">
                    <x-input-label
                        class="form-label"
                        required
                        for="export-type"
                        :content="__('exports.type')" />
                    <x-input-error
                        id="export-type-error"
                        :messages="$errors->get('export_type')" />
                    <select
                        data-auto-submit="input"
                        id="export-type"
                        name="export_type"
                        :hasError="$errors->has('export_type')"
                        aria-labelledby="export-type-error"
                        class="form-select">
                        <option value="">{{ __('petition.filter.no_filter') }}</option>
                        @foreach (ExportType::cases() as $exportType)
                            <option
                                value="{{ $exportType }}"
                                @selected(Form::old('export_type') === $exportType->value)>
                                {{ __('exports.' . $exportType->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-filter-group">
                    <x-input-label
                        class="form-label"
                        required
                        for="petition-type"
                        :content="__('petition.type')" />
                    <x-input-error
                        id="petition-type-error"
                        :messages="$errors->get('petition_type_id')" />
                    <select
                        data-auto-submit="input"
                        id="petition-type"
                        name="petition_type_id"
                        :hasError="$errors->has('petition_type_id')"
                        aria-labelledby="petition-type-error"
                        class="form-select">
                        <option value="">{{ __('petition.filter.no_filter') }}</option>
                        @foreach ($usedPetitionTypes as $petitionType)
                            <option
                                value="{{ $petitionType->id }}"
                                @selected(Form::old('petition_type_id') === $petitionType->id->toString())>
                                {{ $petitionType->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if ($petitionCategories->isNotEmpty())
                    <div class="form-filter-group">
                        <x-input-label
                            class="form-label"
                            for="petition-category"
                            :content="__('petition.category')" />
                        <x-input-error
                            id="petition-category-error"
                            :messages="$errors->get('petition_category_id')" />
                        <select
                            data-auto-submit="input"
                            id="petition-category"
                            name="petition_category_id"
                            :hasError="$errors->has('petition_category_id')"
                            aria-labelledby="petition-category-error"
                            class="form-select">
                            <option value="">{{ __('petition.filter.no_filter') }}</option>
                            @foreach ($petitionCategories as $petitionCategory)
                                <option
                                    value="{{ $petitionCategory->id }}"
                                    @selected(Form::old('petition_category_id') === $petitionCategory->id->toString())>
                                    {{ $petitionCategory->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="form-filter-group">
                    <x-input-label
                        class="form-label"
                        required
                        for="date-from"
                        :content="__('exports.date_from')" />
                    <x-input-error
                        id="date-from-error"
                        :messages="$errors->get('date_from')" />
                    <x-text-input
                        id="date-from"
                        name="date_from"
                        :hasError="$errors->has('date_from')"
                        aria-labelledby="date-from-error"
                        type="date"
                        value="{{ Form::old('date_from') }}" />
                </div>
                <div class="form-filter-group">
                    <x-input-label
                        class="form-label"
                        required
                        for="date-to"
                        :content="__('exports.date_to')" />
                    <x-input-error
                        id="date-to-error"
                        :messages="$errors->get('date_to')" />
                    <x-text-input
                        id="date-to"
                        name="date_to"
                        :hasError="$errors->has('date_to')"
                        aria-labelledby="date-to-error"
                        type="date"
                        value="{{ Form::old('date_to') }}" />
                </div>
                @can(Ability::CREATE, App\Models\PetitionExport::class)
                    <button
                        formaction="{{ route(RouteName::DEPARTMENTS_PETITIONS_EXPORTS_EXPORT, ['department' => $department]) }}"
                        type="submit">
                        {{ __('exports.generate_export') }}
                    </button>
                @endcan
            </form>
        </div>
    </section>
    <section class="mt-5">
        <div class="visually-grouped">
            <table>
                <caption class="visually-hidden">{{ __('petition.overview') }}</caption>
                <thead>
                    <tr>
                        <th scope="col">{{ __('exports.create_date') }}</th>
                        <th scope="col">{{ __('petition.type') }}</th>
                        <th scope="col">{{ __('petition.category') }}</th>
                        <th scope="col">{{ __('exports.date_from') }}</th>
                        <th scope="col">{{ __('exports.date_to') }}</th>
                        <th scope="col">{{ __('exports.type') }}</th>
                        <th scope="col">
                            <span class="visually-hidden">
                                {{ __('general.actions') }}
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($exports as $export)
                        <tr>
                            <td>{{ DisplayDate::date($export->created_at) }}</td>
                            <td>{{ $export->petitionType->name }}</td>
                            <td>{{ $export->petitionCategory ? $export->petitionCategory->name : '-' }}</td>
                            <td>{{ DisplayDate::date($export->date_from) }}</td>
                            <td>{{ DisplayDate::date($export->date_to) }}</td>
                            <td>{{ __('exports.' . $export->type->value) }}</td>
                            <td>
                                <div class="actions">
                                    @can(Ability::VIEW, $export)
                                        <a
                                            class="icon-only"
                                            href="{{ route(RouteName::DEPARTMENTS_PETITIONS_EXPORTS_DOWNLOAD, ['department' => $department, 'petitionExport' => $export->id]) }}">
                                            <x-tabler-download
                                                aria-hidden="true"
                                                focusable="false" />
                                            <span class="visually-hidden">{{ __('exports.download') }}</span>
                                        </a>
                                    @endcan

                                    @can(Ability::DELETE, $export)
                                        <a
                                            class="icon-only icon-only--delete"
                                            href="{{ route(RouteName::DEPARTMENTS_PETITIONS_EXPORTS_DELETE, ['department' => $department, 'petitionExport' => $export->id]) }}">
                                            <x-tabler-trash
                                                aria-hidden="true"
                                                focusable="false" />
                                            <span class="visually-hidden">{{ __('exports.delete') }}</span>
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="5"
                                class="no-results">
                                {{ __('petition.no_exports') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5">&nbsp;</td>
                    </tr>
                </tfoot>
            </table>
            {{ $exports->links() }}
        </div>
    </section>
</x-app-layout>
