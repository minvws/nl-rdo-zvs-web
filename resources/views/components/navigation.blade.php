@use(App\Enums\Ability)
@use(App\Enums\RouteName)
@use(App\Facades\Authentication)
@use(App\Facades\Otp)
@use(App\Enums\Authorization\Permission)
@use(App\Models\Petition)
@use(App\Facades\ActiveDepartment)

<nav
    aria-label="{{ __('general.main-navigation') }}"
    id="main-nav">
    <div class="container container--main-nav">
        @if (Authentication::isLoggedIn() && Otp::isAuthenticated(Authentication::user()))
            @if (Authentication::user()->hasDepartments())
                <x-department-selector />
            @endif

            <ul class="main-nav__list">
                @if (Authentication::user()->hasDepartments() && ActiveDepartment::getActiveDepartment())
                    <x-nav-item
                        :route="departmentRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX)"
                        class="{{  request()->routeIs('departments.petitions.*') && !request()->routeIs('departments.petitions.exports.*') ? 'active-parent' : ''  }}">
                        {{ __('petition.model_plural') }}
                    </x-nav-item>

                    @can(Permission::DECISION_READ->value)
                        <x-nav-item
                            :route="departmentRoute(RouteName::DEPARTMENTS_DECISIONS_INDEX)"
                            class="{{  request()->routeIs('departments.decisions.*') ? 'active-parent' : ''  }}">
                            {{ __('decision.model_plural') }}
                        </x-nav-item>
                    @endcan

                    @can(Permission::CONTACT_READ->value)
                        <x-nav-item
                            :route="departmentRoute(RouteName::DEPARTMENTS_CONTACTS_INDEX)"
                            class="{{  request()->routeIs('departments.contacts.*') ? 'active-parent' : ''  }}">
                            {{ __('contact.model_plural') }}
                        </x-nav-item>
                    @endcan
                @endif

                @can(Permission::ADMIN_PANEL_VIEW->value)
                    <x-nav-item
                        :route="route(RouteName::ADMIN_SHOW)"
                        class="{{  request()->routeIs('admin.*') ? 'active-parent' : ''  }}">
                        {{ __('general.administration') }}
                    </x-nav-item>
                @endcan

                @can(Permission::PETITION_WRITE->value)
                    @if (Authentication::user()->hasDepartments() && ActiveDepartment::getActiveDepartment())
                        <x-nav-item
                            :route="departmentRoute(RouteName::DEPARTMENTS_PETITIONS_EXPORTS_INDEX)"
                            class="{{ request()->routeIs('departments.exports.index') ? 'active-parent' : '' }}">
                            {{ __('exports.export') }}
                        </x-nav-item>
                    @endif
                @endcan
            </ul>

            <ul class="actions">
                <li class="nav-item">
                    @if (Config::get('app.features.notifications'))
                        <x-nav-link
                            class="notification-link"
                            :href="route(RouteName::NOTIFICATIONS_INDEX)">
                            <span class="visually-hidden">Notificaties</span>

                            <x-tabler-bell
                                class="icon"
                                aria-hidden="true" />
                            @if (Authentication::user()->unreadNotifications()->count() > 0)
                                <span class="badge">{{ Authentication::user()->unreadNotifications()->count() }}</span>
                            @endif
                        </x-nav-link>
                    @endif
                </li>
                <li class="nav-item">
                    <x-nav-link
                        class="profile-link avatar"
                        :href="route('profile.edit')">
                        <span class="visually-hidden">{{ __('profile.profile_of') }}:</span>
                        <span aria-hidden="true">{{ Str::customInitials(Authentication::user()->name) }}</span>
                        <span class="visually-hidden">{{ Authentication::user()->name }}</span>
                    </x-nav-link>
                </li>
                <li class="nav-item">
                    <form
                        method="post"
                        action="{{ route('logout') }}"
                        class="inline">
                        @csrf
                        <button
                            class="logout-button"
                            type="submit">
                            {{ __('authentication.logout') }}
                        </button>
                    </form>
                </li>
            </ul>
        @endif
    </div>
</nav>
