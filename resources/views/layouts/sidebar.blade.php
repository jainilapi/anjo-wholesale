<nav id="sidebar" class="sidebar">
    <a class='sidebar-brand'>
        <img src="{{ Helper::logo() }}" style="height: 30px;width: 30px;margin-right: 8px;position: relative;bottom: 3px;" alt="Logo">
        {{ Helper::title() }}
    </a>
    <div class="sidebar-content">
        @if(auth()->check())
        <div class="sidebar-user">
            <img src="{{ auth()->user()->userprofile }}" class="img-fluid rounded-circle mb-2" />
            <div class="fw-bold"> {{ auth()->user()->name }} </div>
            <small> {{ implode(', ', auth()->user()->roles()->pluck('name')->toArray()) }} </small>
        </div>

        <ul class="sidebar-nav">
            <li class="sidebar-header">
                Main
            </li>

            <li class="sidebar-item @if( request()->segment(1) == 'dashboard') active @endif">
                <a href="{{ route('dashboard') }}" class="sidebar-link">
                    <i class="align-middle me-2 fas fa-fw fa-home"></i> <span class="align-middle">Dashboards</span>
                </a>
            </li>

            @if(auth()->user()->isAdmin() || auth()->user()->can('users.index') || auth()->user()->can('roles.index'))
            <li class="sidebar-item @if( request()->segment(1) == 'users' || request()->segment(1) == 'roles') active @endif">
                <a data-bs-target="#dashboards" data-bs-toggle="collapse" class="sidebar-link">
                    <i class="align-middle me-2 fas fa-fw fa-users"></i> <span class="align-middle">User Management</span>
                </a>
                <ul id="dashboards" class="sidebar-dropdown list-unstyled collapse show" data-bs-parent="#sidebar">
                    <li class="sidebar-item"><a class='sidebar-link' href='{{ route('users.index') }}'>Users</a></li>
                    <li class="sidebar-item active"><a class='sidebar-link' href='{{ route('roles.index') }}'>Roles</a></li>
                </ul>
            </li>
            @endif

            @if(auth()->user()->isAdmin() || auth()->user()->can('customers.index'))
            <li class="sidebar-item @if( request()->segment(1) == 'customers') active @endif">
                <a href="{{ route('customers.index') }}" class="sidebar-link">
                    <i class="align-middle me-2 fas fa-fw fa-user-tie"></i> <span class="align-middle">Customers</span>
                </a>
            </li>
            @endif

            @if(auth()->user()->isAdmin() || auth()->user()->can('locations.index'))
            <li class="sidebar-item @if( request()->segment(1) == 'locations') active @endif">
                <a href="{{ route('locations.index') }}" class="sidebar-link">
                    <i class="align-middle me-2 fas fa-fw fa-map-marker-alt"></i> <span class="align-middle">Locations</span>
                </a>
            </li>
            @endif


            <li class="sidebar-item @if(request()->segment(1) == 'gift-redemptions') active @endif">
                <form action="{{ route('logout') }}" method="POST"> @csrf
                    <button type="submit" class="sidebar-link" style="width: 100%;text-align: left;border: none;">
                        <i class="align-middle me-2 fas fa-fw fa-sign-out"></i> <span class="align-middle">Sign out</span>
                    </button>
                </form>
            </li>

        </ul>

        @endif
    </div>
</nav>