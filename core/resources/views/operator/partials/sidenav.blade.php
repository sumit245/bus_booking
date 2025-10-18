<div class="sidebar {{ sidebarVariation()['selector'] }} {{ sidebarVariation()['sidebar'] }} {{ @sidebarVariation()['overlay'] }} {{ @sidebarVariation()['opacity'] }}"
    data-background="{{ getImage('assets/admin/images/sidebar/1.jpg', '400x800') }}">
    <button class="res-sidebar-close-btn"><i class="las la-times"></i></button>
    <div class="sidebar__inner">
        <div class="sidebar__logo">
            <a href="{{ route('operator.dashboard') }}" class="sidebar__main-logo"><img
                    src="{{ getImage(imagePath()['logoIcon']['path'] . '/logo_2.png') }}" alt="@lang('image')"></a>
            <a href="{{ route('operator.dashboard') }}" class="sidebar__logo-shape"><img
                    src="{{ getImage(imagePath()['logoIcon']['path'] . '/favicon.png') }}" alt="@lang('image')"></a>
            <button type="button" class="navbar__expand"></button>
        </div>

        <div class="sidebar__menu-wrapper" id="sidebar__menuWrapper">
            <ul class="sidebar__menu">
                <!-- Dashboard Menu Item -->
                <li class="sidebar-menu-item {{ menuActive('operator.dashboard') }}">
                    <a href="{{ route('operator.dashboard') }}" class="nav-link ">
                        <i class="menu-icon las la-home"></i>
                        <span class="menu-title">@lang('Dashboard')</span>
                    </a>
                </li>

                <!-- Routes Management -->
                <li class="sidebar-menu-item sidebar-dropdown">
                    <a href="javascript:void(0)" class="{{ menuActive('operator.routes*', 3) }}">
                        <i class="menu-icon las la-route"></i>
                        <span class="menu-title">@lang('Routes')</span>
                    </a>
                    <div class="sidebar-submenu {{ menuActive('operator.routes*', 2) }} ">
                        <ul>
                            <li class="sidebar-menu-item {{ menuActive('operator.routes.index') }} ">
                                <a href="{{ route('operator.routes.index') }}" class="nav-link">
                                    <i class="menu-icon las la-dot-circle"></i>
                                    <span class="menu-title">@lang('All Routes')</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item {{ menuActive('operator.routes.create') }} ">
                                <a href="{{ route('operator.routes.create') }}" class="nav-link">
                                    <i class="menu-icon las la-dot-circle"></i>
                                    <span class="menu-title">@lang('Add Route')</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Bus Management -->
                <li class="sidebar-menu-item sidebar-dropdown">
                    <a href="javascript:void(0)" class="{{ menuActive('operator.buses*', 3) }}">
                        <i class="menu-icon las la-bus"></i>
                        <span class="menu-title">@lang('Buses')</span>
                    </a>
                    <div class="sidebar-submenu {{ menuActive('operator.buses*', 2) }} ">
                        <ul>
                            <li class="sidebar-menu-item {{ menuActive('operator.buses.index') }} ">
                                <a href="{{ route('operator.buses.index') }}" class="nav-link">
                                    <i class="menu-icon las la-dot-circle"></i>
                                    <span class="menu-title">@lang('All Buses')</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item {{ menuActive('operator.buses.create') }} ">
                                <a href="{{ route('operator.buses.create') }}" class="nav-link">
                                    <i class="menu-icon las la-dot-circle"></i>
                                    <span class="menu-title">@lang('Add Bus')</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Staff Management -->
                <li class="sidebar-menu-item sidebar-dropdown">
                    <a href="javascript:void(0)" class="{{ menuActive('operator.staff*', 3) }}">
                        <i class="menu-icon las la-users"></i>
                        <span class="menu-title">@lang('Staff Management')</span>
                    </a>
                    <div class="sidebar-submenu {{ menuActive('operator.staff*', 2) }} ">
                        <ul>
                            <li class="sidebar-menu-item {{ menuActive('operator.staff.index') }} ">
                                <a href="{{ route('operator.staff.index') }}" class="nav-link">
                                    <i class="menu-icon las la-dot-circle"></i>
                                    <span class="menu-title">@lang('All Staff')</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item {{ menuActive('operator.staff.create') }} ">
                                <a href="{{ route('operator.staff.create') }}" class="nav-link">
                                    <i class="menu-icon las la-dot-circle"></i>
                                    <span class="menu-title">@lang('Add Staff')</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Crew Assignment -->
                <li class="sidebar-menu-item sidebar-dropdown">
                    <a href="javascript:void(0)" class="{{ menuActive('operator.crew*', 3) }}">
                        <i class="menu-icon las la-user-tie"></i>
                        <span class="menu-title">@lang('Crew Assignment')</span>
                    </a>
                    <div class="sidebar-submenu {{ menuActive('operator.crew*', 2) }} ">
                        <ul>
                            <li class="sidebar-menu-item {{ menuActive('operator.crew.index') }} ">
                                <a href="{{ route('operator.crew.index') }}" class="nav-link">
                                    <i class="menu-icon las la-dot-circle"></i>
                                    <span class="menu-title">@lang('All Assignments')</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item {{ menuActive('operator.crew.create') }} ">
                                <a href="{{ route('operator.crew.create') }}" class="nav-link">
                                    <i class="menu-icon las la-dot-circle"></i>
                                    <span class="menu-title">@lang('Assign Crew')</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Attendance Management -->
                <li class="sidebar-menu-item sidebar-dropdown">
                    <a href="javascript:void(0)" class="{{ menuActive('operator.attendance*', 3) }}">
                        <i class="menu-icon las la-calendar-check"></i>
                        <span class="menu-title">@lang('Attendance')</span>
                    </a>
                    <div class="sidebar-submenu {{ menuActive('operator.attendance*', 2) }} ">
                        <ul>
                            <li class="sidebar-menu-item {{ menuActive('operator.attendance.index') }} ">
                                <a href="{{ route('operator.attendance.index') }}" class="nav-link">
                                    <i class="menu-icon las la-dot-circle"></i>
                                    <span class="menu-title">@lang('All Attendance')</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item {{ menuActive('operator.attendance.create') }} ">
                                <a href="{{ route('operator.attendance.create') }}" class="nav-link">
                                    <i class="menu-icon las la-dot-circle"></i>
                                    <span class="menu-title">@lang('Mark Attendance')</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Schedule Management -->
                <li class="sidebar-menu-item sidebar-dropdown">
                    <a href="javascript:void(0)" class="{{ menuActive('operator.schedules*', 3) }}">
                        <i class="menu-icon las la-calendar"></i>
                        <span class="menu-title">@lang('Schedule')</span>
                    </a>
                    <div class="sidebar-submenu {{ menuActive('operator.schedules*', 2) }} ">
                        <ul>
                            <li class="sidebar-menu-item {{ menuActive('operator.schedules.index') }} ">
                                <a href="{{ route('operator.schedules.index') }}" class="nav-link">
                                    <i class="menu-icon las la-dot-circle"></i>
                                    <span class="menu-title">@lang('All Schedules')</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item {{ menuActive('operator.schedules.create') }} ">
                                <a href="{{ route('operator.schedules.create') }}" class="nav-link">
                                    <i class="menu-icon las la-dot-circle"></i>
                                    <span class="menu-title">@lang('Add Schedule')</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Operator Bookings Management -->
                <li class="sidebar-menu-item sidebar-dropdown">
                    <a href="javascript:void(0)" class="{{ menuActive('operator.bookings*', 3) }}">
                        <i class="menu-icon las la-ticket-alt"></i>
                        <span class="menu-title">@lang('My Bookings')</span>
                    </a>
                    <div class="sidebar-submenu {{ menuActive('operator.bookings*', 2) }} ">
                        <ul>
                            <li class="sidebar-menu-item {{ menuActive('operator.bookings.index') }} ">
                                <a href="{{ route('operator.bookings.index') }}" class="nav-link">
                                    <i class="menu-icon las la-dot-circle"></i>
                                    <span class="menu-title">@lang('All Bookings')</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item {{ menuActive('operator.bookings.create') }} ">
                                <a href="{{ route('operator.bookings.create') }}" class="nav-link">
                                    <i class="menu-icon las la-dot-circle"></i>
                                    <span class="menu-title">@lang('Block Seats')</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Revenue Management -->
                <li class="sidebar-menu-item sidebar-dropdown">
                    <a href="javascript:void(0)" class="{{ menuActive('operator.revenue*', 3) }}">
                        <i class="menu-icon las la-money-bill-wave"></i>
                        <span class="menu-title">@lang('Revenue')</span>
                    </a>
                    <div class="sidebar-submenu {{ menuActive('operator.revenue*', 2) }} ">
                        <ul>
                            <li class="sidebar-menu-item {{ menuActive('operator.revenue.dashboard') }} ">
                                <a href="{{ route('operator.revenue.dashboard') }}" class="nav-link">
                                    <i class="menu-icon las la-dot-circle"></i>
                                    <span class="menu-title">@lang('Dashboard')</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item {{ menuActive('operator.revenue.reports') }} ">
                                <a href="{{ route('operator.revenue.reports') }}" class="nav-link">
                                    <i class="menu-icon las la-dot-circle"></i>
                                    <span class="menu-title">@lang('Reports')</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item {{ menuActive('operator.revenue.payouts') }} ">
                                <a href="{{ route('operator.revenue.payouts') }}" class="nav-link">
                                    <i class="menu-icon las la-dot-circle"></i>
                                    <span class="menu-title">@lang('Payouts')</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Profile Management -->
                <li class="sidebar-menu-item sidebar-dropdown">
                    <a href="javascript:void(0)" class="{{ menuActive('operator.profile*', 3) }}">
                        <i class="menu-icon las la-user"></i>
                        <span class="menu-title">@lang('Profile')</span>
                    </a>
                    <div class="sidebar-submenu {{ menuActive('operator.profile*', 2) }} ">
                        <ul>
                            <li class="sidebar-menu-item {{ menuActive('operator.profile') }} ">
                                <a href="{{ route('operator.profile') }}" class="nav-link">
                                    <i class="menu-icon las la-dot-circle"></i>
                                    <span class="menu-title">@lang('View Profile')</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item {{ menuActive('operator.change-password') }} ">
                                <a href="{{ route('operator.change-password') }}" class="nav-link">
                                    <i class="menu-icon las la-dot-circle"></i>
                                    <span class="menu-title">@lang('Change Password')</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</div>
