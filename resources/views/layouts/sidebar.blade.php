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
                    <i class="align-middle me-2 fas fa-fw fa-users"></i> <span class="align-middle">Internal Users Management</span>
                </a>
                <ul id="dashboards" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                    <li class="sidebar-item @if(request()->segment(1) == 'users') active @endif"><a class='sidebar-link' href='{{ route('users.index') }}'>Users</a></li>
                    <li class="sidebar-item @if(request()->segment(1) == 'roles') active @endif"><a class='sidebar-link' href='{{ route('roles.index') }}'>Roles</a></li>
                </ul>
            </li>
            @endif

            @if(auth()->user()->isAdmin() || auth()->user()->can('customers.index') || auth()->user()->can('customer-locations.index'))
            <li class="sidebar-item @if( request()->segment(1) == 'customers' || request()->segment(1) == 'customer-locations') active @endif">
                <a data-bs-target="#customerMgmt" data-bs-toggle="collapse" class="sidebar-link">
                    <i class="align-middle me-2 fas fa-fw fa-user-tie"></i> <span class="align-middle">Customers Management</span>
                </a>
                <ul id="customerMgmt" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                    <li class="sidebar-item @if(request()->segment(1) == 'customers') active @endif"><a class='sidebar-link' href='{{ route('customers.index') }}'>Customers</a></li>
                    <li class="sidebar-item @if(request()->segment(1) == 'customer-locations') active @endif"><a class='sidebar-link' href='{{ route('customer-locations.index') }}'>Customer Locations</a></li>
                </ul>
            </li>
            @endif

            @if(auth()->user()->isAdmin() || auth()->user()->can('suppliers.index'))
            <li class="sidebar-item @if( request()->segment(1) == 'suppliers') active @endif">
                <a href="{{ route('suppliers.index') }}" class="sidebar-link">
                    <i class="align-middle me-2 fas fa-fw fa-user"></i> <span class="align-middle"> Suppliers </span>
                </a>
            </li>
            @endif

            @if(auth()->user()->isAdmin() || auth()->user()->can('warehouses.index'))
            <li class="sidebar-item @if( request()->segment(1) == 'warehouses') active @endif">
                <a href="{{ route('warehouses.index') }}" class="sidebar-link">
                    <i class="align-middle me-2 fas fa-fw fa-warehouse"></i> <span class="align-middle">Warehouses</span>
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

            @if(auth()->user()->isAdmin() || auth()->user()->can('categories.index') || auth()->user()->can('products.index') || auth()->user()->can('brands.index'))
            <li class="sidebar-item @if( request()->segment(1) == 'categories' || request()->segment(1) == 'products' || request()->segment(1) == 'brands') active @endif">
                <a data-bs-target="#catalogMenu" data-bs-toggle="collapse" class="sidebar-link">
                    <i class="align-middle me-2 fas fa-fw fa-boxes"></i> <span class="align-middle">Catalog</span>
                </a>
                <ul id="catalogMenu" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                    @if(auth()->user()->isAdmin() || auth()->user()->can('categories.index'))
                    <li class="sidebar-item @if(request()->segment(1) == 'categories') active @endif"><a class='sidebar-link' href='{{ route('categories.index') }}'>Categories</a></li>
                    @endif
                    @if(auth()->user()->isAdmin() || auth()->user()->can('products.index'))
                    <li class="sidebar-item @if(request()->segment(1) == 'products') active @endif"><a class='sidebar-link' href='{{ route('products.index') }}'>Products</a></li>
                    @endif
                    @if(auth()->user()->isAdmin() || auth()->user()->can('brands.index'))
                    <li class="sidebar-item @if(request()->segment(1) == 'brands') active @endif"><a class='sidebar-link' href='{{ route('brands.index') }}'>Brands</a></li>
                    @endif
                </ul>
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