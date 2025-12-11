@use(App\Enums\Authorization\Permission)
@use(App\Enums\RouteName)
@can('update', [$petition])
    <div class="button-container">
        @foreach ($petitionDeliverableTypes as $petitionDeliverableType)
            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_PETITION_DELIVERABLE_CREATE, ['department' => $petition->department->slug, 'petition' => $petition, 'petitionDeliverableType' => $petitionDeliverableType]) }}">
                {{ __(sprintf('petition_deliverable.petition_deliverable_type.%s', $petitionDeliverableType->value)) }}
            </a>
        @endforeach
    </div>
@endcan
