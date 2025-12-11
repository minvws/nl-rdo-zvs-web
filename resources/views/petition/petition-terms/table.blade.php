@use(App\Enums\TermType)
@use(App\Facades\DisplayDate)

<section class="mt-5">
    <h2>{{ __('term.model_plural') }}</h2>
    <table>
        <thead>
            <tr>
                <th scope="col">{{ __('term.type') }}</th>
                <th
                    scope="col"
                    class="align-right">
                    {{ __('term.start_date') }}
                </th>
                <th
                    scope="col"
                    class="align-right">
                    {{ __('term.duration_in_days') }}
                </th>
                <th
                    scope="col"
                    class="align-right">
                    {{ __('term.end_date') }}
                </th>
                <th
                    scope="col"
                    class="align-right">
                    {{ __('term.penalty_amount_in_euros') }}
                </th>
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
            @forelse ($petitionTerms as $term)
                <tr
                    @class([
                        'suspended' => $term->type->value === TermType::SUSPENSION->value,
                        'penalty' => $term->type->value === TermType::PENALTY->value,
                    ])>
                    <th
                        scope="row"
                        title="{{ $term->description }}">
                        @switch($term->type->value)
                            @case(TermType::SUSPENSION->value)
                                <x-tabler-arrow-up
                                    aria-hidden="true"
                                    focusable="false" />

                                @break
                            @case(TermType::SPECIFIED_ADJOURNMENT->value)
                                <x-tabler-arrow-up
                                    aria-hidden="true"
                                    focusable="false" />

                                @break
                            @case(TermType::PENALTY->value)
                                <x-tabler-corner-down-right
                                    aria-hidden="true"
                                    focusable="false" />

                                @break
                        @endswitch
                        {{ __('term.term_type.' . $term->type->value) }}
                    </th>
                    <td class="align-right">{{ DisplayDate::date($term->start_date) }}</td>
                    <td class="align-right">
                        {{ $term->duration_in_days }}
                    </td>
                    <td class="align-right">{{ $term->end_date ? DisplayDate::date($term->end_date) : '' }}</td>
                    <td class="align-right">
                        @if ($term->penalty_amount_in_euros > 0)
                            {{ Number::currency($term->penalty_amount_in_euros, 'EUR', 'nl', 0) }}
                        @endif
                    </td>
                    <td>
                        @can(Permission::PETITION_WRITE->value)
                            <a
                                class="icon-only"
                                href="{{
                                    route(RouteName::DEPARTMENTS_PETITIONS_TERMS_EDIT, [
                                        'department' => $petition->department,
                                        'petition' => $petition,
                                        'petitionTerm' => $term,
                                    ])
                                }}">
                                <span class="visually-hidden">{{ __('general.view') }} {{ $term->type->value }}</span>
                                <x-tabler-chevron-right
                                    aria-hidden="true"
                                    focusable="false" />
                            </a>
                        @else
                            &nbsp;
                        @endcan
                    </td>
                    <td>
                        @can(Permission::PETITION_WRITE->value)
                            <a
                                class="icon-only"
                                href="{{
                                    route(RouteName::DEPARTMENTS_PETITIONS_TERMS_DELETE, [
                                        'department' => $petition->department,
                                        'petition' => $petition,
                                        'petitionTerm' => $term,
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
                        {{ __('term.no_records') }}
                    </td>
                </tr>
            @endforelse
            @if ($draftTerm)
                <tr class="draft-term">
                    <th scope="row">
                        {{ __('draft_term.model_singular') }}
                    </th>
                    <td class="align-right">{{ DisplayDate::date($draftTerm->start_date) }}</td>
                    <td class="align-right">-</td>
                    <td class="align-right">-</td>
                    <td class="align-right">&nbsp;</td>
                    <td>
                        @can(Permission::PETITION_WRITE->value)
                            <a
                                class="icon-only"
                                href="{{
                                    route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_EDIT, [
                                        'department' => $petition->department,
                                        'petition' => $petition,
                                    ])
                                }}">
                                <span class="visually-hidden">
                                    {{ __('general.view') }} {{ __('draft_term.model_singular') }}
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
                                    route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_DELETE, [
                                        'department' => $petition->department,
                                        'petition' => $petition,
                                    ])
                                }}">
                                <span class="visually-hidden">
                                    {{ __('general.delete') }} {{ __('draft_term.model_singular') }}
                                </span>
                                <x-tabler-trash
                                    aria-hidden="true"
                                    focusable="false" />
                            </a>
                        @else
                            &nbsp;
                        @endcan
                    </td>
                </tr>
            @endif
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6">
                    <x-petition.petition-terms.create-buttons :petition="$petition" />
                </td>
            </tr>
        </tfoot>
    </table>
</section>
