<div class="sidebar">
    <div class="sidebar__menu">
        <ul class="sidebar-menu">
            <li class="sidebar-menu-item {{ request()->routeIs('agent.dashboard') ? 'active' : '' }}">
                <a href="{{ route('agent.dashboard') }}" class="sidebar-menu-link">
                    <span class="sidebar-menu-icon">
                        <i class="las la-home"></i>
                    </span>
                    <span class="sidebar-menu-text">@lang('Dashboard')</span>
                </a>
            </li>
            <li class="sidebar-menu-item {{ request()->routeIs('agent.search*') ? 'active' : '' }}">
                <a href="{{ route('agent.search') }}" class="sidebar-menu-link">
                    <span class="sidebar-menu-icon">
                        <i class="las la-search"></i>
                    </span>
                    <span class="sidebar-menu-text">@lang('Search Buses')</span>
                </a>
            </li>
            <li class="sidebar-menu-item {{ request()->routeIs('agent.bookings*') ? 'active' : '' }}">
                <a href="{{ route('agent.bookings') }}" class="sidebar-menu-link">
                    <span class="sidebar-menu-icon">
                        <i class="las la-ticket-alt"></i>
                    </span>
                    <span class="sidebar-menu-text">@lang('My Bookings')</span>
                </a>
            </li>
            <li class="sidebar-menu-item {{ request()->routeIs('agent.earnings*') ? 'active' : '' }}">
                <a href="{{ route('agent.earnings') }}" class="sidebar-menu-link">
                    <span class="sidebar-menu-icon">
                        <i class="las la-chart-line"></i>
                    </span>
                    <span class="sidebar-menu-text">@lang('Earnings')</span>
                </a>
            </li>
            <li class="sidebar-menu-item {{ request()->routeIs('agent.profile*') ? 'active' : '' }}">
                <a href="{{ route('agent.profile') }}" class="sidebar-menu-link">
                    <span class="sidebar-menu-icon">
                        <i class="las la-user"></i>
                    </span>
                    <span class="sidebar-menu-text">@lang('Profile')</span>
                </a>
            </li>
        </ul>
    </div>
</div>
