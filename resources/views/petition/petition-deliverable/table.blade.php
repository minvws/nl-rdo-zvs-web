<section class="mt-5">
    <h2>{{ __('petition_deliverable.model_plural') }}</h2>
    <table>
        <thead>
            <tr>
                <th scope="col">{{ __('petition_deliverable.type') }}</th>
                <th scope="col">{{ __('petition_deliverable.deadline_at') }}</th>
                <th scope="col">{{ __('petition_deliverable.description') }}</th>
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
            @forelse ($petitionDeliverables as $petitionDeliverable)
                <tr>
                    <th scope="row">
                        {{ __(sprintf('petition_deliverable.petition_deliverable_type.%s', $petitionDeliverable->type->value)) }}
                    </th>
                    <td>
                        {{ DisplayDate::date($petitionDeliverable->deadline_at) }}
                    </td>
                    <td>
                        {{ $petitionDeliverable->description }}
                    </td>
                    <td>
                        @can('update', [$petition])
                            <a
                                class="icon-only"
                                href="{{
                                    route(RouteName::DEPARTMENTS_PETITIONS_PETITION_DELIVERABLE_EDIT, [
                                        'department' => $petition->department->slug,
                                        'petition' => $petition,
                                        'petitionDeliverable' => $petitionDeliverable,
                                    ])
                                }}">
                                <span class="visually-hidden">
                                    {{ __('general.view') }} {{ $petitionDeliverable->type->value }}
                                </span>
                                <x-tabler-chevron-right
                                    aria-hidden="true"
                                    focusable="false" />
                            </a>
                        @else
                            &nbsp;
                        @endcan
                    </td>
                    <td>
                        @can('update', [$petition])
                            <a
                                class="icon-only"
                                href="{{
                                    route(RouteName::DEPARTMENTS_PETITIONS_PETITION_DELIVERABLE_DELETE, [
                                        'department' => $petition->department->slug,
                                        'petition' => $petition,
                                        'petitionDeliverable' => $petitionDeliverable,
                                    ])
                                }}"
                                aria-label="{{ __('general.delete') }}">
                                <x-tabler-trash
                                    aria-hidden="true"
                                    focusable="false" />
                            </a>
                        @else
                            &nbsp;
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        {{ __('petition_deliverable.no_records') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6">
                    <x-petition.petition-deliverables.create-buttons :petition="$petition" />
                </td>
            </tr>
        </tfoot>
    </table>
</section>
