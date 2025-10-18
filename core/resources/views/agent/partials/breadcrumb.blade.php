<div class="page-breadcrumb d-none d-md-flex align-items-center mb-3">
    <div class="breadcrumb-title pr-3">{{ $pageTitle ?? 'Agent Panel' }}</div>
    <div class="pl-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('agent.dashboard') }}">@lang('Dashboard')</a></li>
                @yield('breadcrumb')
            </ol>
        </nav>
    </div>
</div>
