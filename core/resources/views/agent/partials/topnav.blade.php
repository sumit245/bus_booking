<!-- Top Navigation - Hidden on mobile, visible on tablet and desktop -->
<div class="topbar d-none d-md-block">
    <div class="topbar__left">
        <div class="topbar__logo"></div>
        <a href="{{ route('agent.dashboard') }}">
            <img src="{{ getImage(imagePath()['logoIcon']['path'] . '/logo.png') }}" alt="@lang('logo')"
                class="logo-img">
            <span class="logo-text">Ghumantoo</span>
        </a>
    </div>
</div>
<div class="topbar__right">
    <ul class="topbar__menu">
        <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                <div class="user-avatar">
                    <span class="user-avatar__name">{{ auth('agent')->user()->name ?? 'Agent' }}</span>
                    <span class="user-avatar__status">
                        @if (auth('agent')->user()->status === 'active')
                            <span class="badge badge--success">@lang('Active')</span>
                        @elseif(auth('agent')->user()->status === 'pending')
                            <span class="badge badge--warning">@lang('Pending')</span>
                        @else
                            <span class="badge badge--danger">@lang('Suspended')</span>
                        @endif
                    </span>
                </div>
            </a>
            <ul class="dropdown-menu">
                <li><a href="{{ route('agent.profile') }}"><i class="las la-user"></i>@lang('Profile')</a></li>
                <li><a href="{{ route('agent.earnings') }}"><i class="las la-chart-line"></i>@lang('Earnings')</a>
                </li>
                <li class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('agent.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-link text-danger">
                            <i class="las la-sign-out-alt"></i>@lang('Logout')
                        </button>
                    </form>
                </li>
            </ul>
        </li>
    </ul>
</div>
</div>
