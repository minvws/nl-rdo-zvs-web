@use(App\Enums\Authorization\Permission)
@use(App\Enums\RouteName)

<x-app-layout>
    <x-slot name="header">
        <div class="action-bar">
            <h1>
                {{ __('tag.model_plural') }}
            </h1>
            @can(Permission::TAG_WRITE->value)
                <a
                    class="button"
                    href="{{ route(RouteName::DEPARTMENTS_ADMIN_TAGS_CREATE, ['department' => $department]) }}">
                    {{ __('tag.create') }}
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
                    {{ __('tag.model_plural') }}
                </caption>
                <thead>
                    <tr>
                        <th scope="col">{{ __('tag.model_singular') }}</th>
                        <th scope="col">{{ __('tag.description') }}</th>
                        <th scope="col">{{ __('tag.active') }}</th>
                        <th scope="col">{{ __('tag.usage_count') }}</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tags as $tag)
                        <tr>
                            <th scope="row">
                                <x-tag.chip :tag="$tag" />
                            </th>
                            <td>{{ $tag->description ?? '-' }}</td>
                            <td>
                                @if ($tag->active)
                                    {{ __('general.yes') }}
                                @else
                                    {{ __('general.no') }}
                                @endif
                            </td>
                            <td>{{ $tag->petitions_count }}</td>
                            <td>
                                <div class="actions">
                                    @can(Permission::TAG_WRITE->value)
                                        <a
                                            class="icon-only"
                                            href="{{ route(RouteName::DEPARTMENTS_ADMIN_TAGS_EDIT, ['department' => $department, 'tag' => $tag->id]) }}"
                                            aria-label="{{ __('tag.edit') }}">
                                            <x-tabler-chevron-right
                                                aria-hidden="true"
                                                focusable="false" />
                                        </a>
                                        <a
                                            class="icon-only"
                                            href="{{
                                                confirmRoute(
                                                    route(RouteName::DEPARTMENTS_ADMIN_TAGS_DELETE, ['department' => $department, 'tag' => $tag->id]),
                                                    route(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department]),
                                                    trans_choice('tag.delete_confirmation_text', $tag->petitions_count, ['name' => $tag->name]),
                                                    'DELETE',
                                                )
                                            }}"
                                            aria-label="{{ __('tag.delete') }}">
                                            <x-tabler-trash
                                                aria-hidden="true"
                                                focusable="false" />
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">{{ __('tag.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr></tr>
                </tfoot>
            </table>
            {{ $tags->links() }}
        </div>
    </section>
</x-app-layout>
