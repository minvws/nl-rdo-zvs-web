@use(App\Models\PolicyDepartment)

@section("pageTitle", __("policy_department.edit"))

<div class="petition-property__block petition-property__block--active">
    @ifHtmx
        <header class="petition-property__header">
            <h2 class="petition-property__title">{{ __("policy_department.model_singular") }}</h2>

            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_POLICY_DEPARTMENT_SHOW, ["department" => $petition->department->slug, "petition" => $petition]) }}"
                class="icon-only petition-property__edit"
                hx-push-url="false"
                hx-swap="innerHTML"
                hx-target="#policy-department-block">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">{{ __("policy_department.edit") }}</span>
            </a>
        </header>
    @endifHtmx

    <div class="petition-property__content">
        <form
            method="post"
            action="{{ route(RouteName::DEPARTMENTS_PETITIONS_POLICY_DEPARTMENT_EDIT, ["department" => $petition->department->slug, "petition" => $petition]) }}"
            hx-push-url="false"
            hx-swap="innerHTML"
            hx-target="#policy-department-block">
            @csrf
            <h3 class="form-section__title form-section__title--border">
                {{ __("policy_department.edit") }}
            </h3>
            <div class="form-input-group">
                <x-input-label
                    class="visually-hidden"
                    for="policy_department"
                    :content="__('policy_department.model_singular')" />
                <x-input-error
                    id="policy-department-error"
                    :messages="$errors->get('policy_department_id')" />
                @foreach ($policyDepartments as $policyDepartment)
                    <div class="checkbox">
                        <input
                            id="{{ $policyDepartment->id }}"
                            type="checkbox"
                            name="policy_department_ids[]"
                            value="{{ $policyDepartment->id->toString() }}"
                            @checked(in_array(
                                $policyDepartment->id,
                                $petition->policyDepartments
                                    ->map(static function (PolicyDepartment $policyDepartment): string {
                                        return $policyDepartment->id->toString();
                                    })
                                    ->toArray(),
                            )) />
                        <x-input-label
                            for="{{$policyDepartment->id}}"
                            :content="$policyDepartment->name" />
                    </div>
                @endforeach
            </div>
            @if ($errors->any())
                <x-notification type="danger">
                    <p>{{ __("validation.global_message") }}</p>
                </x-notification>
            @endif

            <div class="button-container">
                <x-primary-button>{{ __("general.save") }}</x-primary-button>
                <a
                    class="button"
                    @ifHtmx
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_POLICY_DEPARTMENT_SHOW, ["department" => $petition->department->slug, "petition" => $petition]) }}"
                        hx-target="#policy-department-block"
                        hx-swap="innerHTML"
                    @else
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ["department" => $petition->department->slug, "petition" => $petition]) }}"
                    @endifHtmx>
                    {{ __("general.cancel") }}
                </a>
            </div>
        </form>
    </div>
</div>
