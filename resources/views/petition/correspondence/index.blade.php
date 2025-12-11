@section('pageTitle', __('petition_correspondence.word_templates_overview'))

<x-form-layout>
    <x-petition-header-details
        :petition="$petition"
        :hasBackLink="true"
        :backLinkRoute="route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ['department' => $petition->department->slug, 'petition' => $petition])"
        :backLinkLabel="__('general.back_to_petition')" />

    <section class="mt-5">
        <div class="visually-grouped">
            <h2>{{ __('petition_correspondence.word_templates') }}</h2>

            <table>
                <thead>
                    <tr>
                        <th scope="col">{{ __('petition_correspondence.template_name') }}</th>
                        <th scope="col">
                            <span class="visually-hidden">{{ __('general.actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($wordTemplates as $template)
                        <tr>
                            <th scope="row">{{ $template->filename }}</th>
                            <td>
                                <a
                                    class="button"
                                    href="{{ route(RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_WORD_TEMPLATE_DOWNLOAD, ['department' => $petition->department->slug, 'petition' => $petition, 'word_template_id' => $template->word_template_id]) }}">
                                    {{ __('general.generate') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">
                                <p>{{ __('petition_correspondence.no_word_templates') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr></tr>
                </tfoot>
            </table>
        </div>
    </section>
</x-form-layout>
