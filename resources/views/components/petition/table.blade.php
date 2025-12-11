@use(App\Facades\DisplayDate)
@use(App\Enums\RouteName)

@props([
    'footer' => null,
    'petitions' => [],
    'detachUrl' => null,
    'title',
])
<section class="mt-5">
    <h2>{{ $title }}</h2>
    <table>
        <thead>
            <tr>
                <th scope="col">{{ __('petition.number') }}</th>
                <th scope="col">{{ __('petition.type') }}</th>
                <th scope="col">{{ __('petition.name') }}</th>
                <th scope="col">{{ __('petition.start_date') }}</th>
                <th scope="col">{{ __('petition.assigned_user') }}</th>
                <th scope="col">{{ __('petition.status') }}</th>
                <th
                    class="action-column"
                    scope="col">
                    &nbsp;
                </th>
                <th
                    class="action-column"
                    scope="col">
                    &nbsp;
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($petitions as $petition)
                <tr>
                    <th scope="row">{{ $petition->number }}</th>
                    <td>{{ $petition->petitionType->name }}</td>
                    <td>{{ $petition->name }}</td>
                    <td>{{ DisplayDate::date($petition->date_of_entry) }}</td>
                    <td>{{ $petition->assignedUser?->name }}</td>
                    <td>{{ $petition->petitionStatus->status }}</td>
                    <td>
                        <a
                            class="icon-only"
                            href="{{
                                route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                                    'department' => $petition->department->slug,
                                    'petition' => $petition,
                                ])
                            }}">
                            <span class="visually-hidden">{{ __('general.view') }} {{ $petition->name }}</span>
                            <x-tabler-chevron-right
                                aria-hidden="true"
                                focusable="false" />
                        </a>
                    </td>
                    <td>
                        @if ($detachUrl !== null)
                            <a
                                class="icon-only"
                                href="{{ $detachUrl($petition) }}">
                                <span class="visually-hidden">{{ $petition->name }} {{ __('petition.detach') }}</span>
                                <x-tabler-unlink
                                    aria-hidden="true"
                                    focusable="false" />
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">
                        {{ __('petition.no_attached_petitions') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">
                    {{ $footer }}
                </td>
            </tr>
        </tfoot>
    </table>
</section>
