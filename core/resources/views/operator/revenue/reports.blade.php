@extends('operator.layouts.app')

@section('panel')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">@lang('Revenue Reports')</h4>
                            <div>
                                <button type="button" class="btn btn-primary" data-toggle="modal"
                                    data-target="#generateReportModal">
                                    <i class="las la-plus"></i> @lang('Generate Report')
                                </button>
                                <button type="button" class="btn btn-success" data-toggle="modal"
                                    data-target="#exportModal">
                                    <i class="las la-download"></i> @lang('Export to Excel')
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <form method="GET" class="row mb-4">
                            <div class="col-md-3">
                                <label>@lang('Start Date')</label>
                                <input type="date" name="start_date" class="form-control"
                                    value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-3">
                                <label>@lang('End Date')</label>
                                <input type="date" name="end_date" class="form-control"
                                    value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-3">
                                <label>@lang('Report Type')</label>
                                <select name="report_type" class="form-control">
                                    <option value="">@lang('All Types')</option>
                                    <option value="daily" {{ request('report_type') == 'daily' ? 'selected' : '' }}>
                                        @lang('Daily')</option>
                                    <option value="weekly" {{ request('report_type') == 'weekly' ? 'selected' : '' }}>
                                        @lang('Weekly')</option>
                                    <option value="monthly" {{ request('report_type') == 'monthly' ? 'selected' : '' }}>
                                        @lang('Monthly')</option>
                                    <option value="custom" {{ request('report_type') == 'custom' ? 'selected' : '' }}>
                                        @lang('Custom')</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">@lang('Filter')</button>
                                    <a href="{{ route('operator.revenue.reports') }}"
                                        class="btn btn-secondary">@lang('Reset')</a>
                                </div>
                            </div>
                        </form>

                        <!-- Reports Table -->
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'report_date', 'order' => request('order') == 'asc' ? 'desc' : 'asc']) }}"
                                                class="text-dark">
                                                @lang('Date')
                                                @if (request('sort') == 'report_date')
                                                    <i
                                                        class="las la-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="las la-sort text-muted"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'report_type', 'order' => request('order') == 'asc' ? 'desc' : 'asc']) }}"
                                                class="text-dark">
                                                @lang('Type')
                                                @if (request('sort') == 'report_type')
                                                    <i
                                                        class="las la-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="las la-sort text-muted"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'total_tickets', 'order' => request('order') == 'asc' ? 'desc' : 'asc']) }}"
                                                class="text-dark">
                                                @lang('Tickets')
                                                @if (request('sort') == 'total_tickets')
                                                    <i
                                                        class="las la-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="las la-sort text-muted"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'total_revenue', 'order' => request('order') == 'asc' ? 'desc' : 'asc']) }}"
                                                class="text-dark">
                                                @lang('Total Revenue')
                                                @if (request('sort') == 'total_revenue')
                                                    <i
                                                        class="las la-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="las la-sort text-muted"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'user_bookings_revenue', 'order' => request('order') == 'asc' ? 'desc' : 'asc']) }}"
                                                class="text-dark">
                                                @lang('User Bookings')
                                                @if (request('sort') == 'user_bookings_revenue')
                                                    <i
                                                        class="las la-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="las la-sort text-muted"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'operator_bookings_revenue', 'order' => request('order') == 'asc' ? 'desc' : 'asc']) }}"
                                                class="text-dark">
                                                @lang('Operator Bookings')
                                                @if (request('sort') == 'operator_bookings_revenue')
                                                    <i
                                                        class="las la-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="las la-sort text-muted"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'platform_commission', 'order' => request('order') == 'asc' ? 'desc' : 'asc']) }}"
                                                class="text-dark">
                                                @lang('Platform Commission')
                                                @if (request('sort') == 'platform_commission')
                                                    <i
                                                        class="las la-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="las la-sort text-muted"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'net_payable', 'order' => request('order') == 'asc' ? 'desc' : 'asc']) }}"
                                                class="text-dark">
                                                @lang('Net Payable')
                                                @if (request('sort') == 'net_payable')
                                                    <i
                                                        class="las la-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="las la-sort text-muted"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>@lang('Action')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reports as $report)
                                        <tr>
                                            <td>{{ $report->report_date->format('M d, Y') }}</td>
                                            <td>
                                                <span class="badge badge-info">{{ ucfirst($report->report_type) }}</span>
                                            </td>
                                            <td>{{ $report->total_tickets }}</td>
                                            <td>₹{{ number_format($report->total_revenue, 2) }}</td>
                                            <td>₹{{ number_format($report->user_bookings_revenue, 2) }}</td>
                                            <td>₹{{ number_format($report->operator_bookings_revenue, 2) }}</td>
                                            <td>₹{{ number_format($report->platform_commission, 2) }}</td>
                                            <td>
                                                <strong>₹{{ number_format($report->net_payable, 2) }}</strong>
                                            </td>
                                            <td>
                                                <a href="{{ route('operator.revenue.reports.show', $report->id) }}"
                                                    class="btn btn-sm btn-primary">
                                                    <i class="las la-eye"></i> @lang('View')
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center">
                                                <div class="py-4">
                                                    <i class="las la-chart-line fs-1 text-muted"></i>
                                                    <p class="mt-2 text-muted">@lang('No revenue reports found')</p>
                                                    <button type="button" class="btn btn-primary" data-toggle="modal"
                                                        data-target="#generateReportModal">
                                                        @lang('Generate Your First Report')
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Controls -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <form method="GET" class="form-inline">
                                    @foreach (request()->except(['per_page', 'page']) as $key => $value)
                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                    @endforeach
                                    <label class="mr-2">@lang('Rows per page'):</label>
                                    <select name="per_page" class="form-control form-control-sm"
                                        onchange="this.form.submit()">
                                        <option value="10" {{ request('per_page', 20) == 10 ? 'selected' : '' }}>10
                                        </option>
                                        <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20
                                        </option>
                                        <option value="50" {{ request('per_page', 20) == 50 ? 'selected' : '' }}>50
                                        </option>
                                        <option value="100" {{ request('per_page', 20) == 100 ? 'selected' : '' }}>100
                                        </option>
                                    </select>
                                </form>
                            </div>
                            <div>
                                @if ($reports->hasPages())
                                    {{ $reports->appends(request()->except('page'))->links() }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Generate Report Modal -->
    <div class="modal fade" id="generateReportModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ route('operator.revenue.reports.generate') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">@lang('Generate Revenue Report')</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>@lang('Date')</label>
                            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}"
                                required>
                            <small class="form-text text-muted">@lang('Select the date for which to generate the report')</small>
                        </div>
                        <div class="form-group">
                            <label>@lang('Report Type')</label>
                            <select name="type" class="form-control" required>
                                <option value="daily">@lang('Daily Report')</option>
                                <option value="weekly">@lang('Weekly Report')</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('Cancel')</button>
                        <button type="submit" class="btn btn-primary">@lang('Generate Report')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="GET" action="{{ route('operator.revenue.export') }}">
                    <div class="modal-header">
                        <h5 class="modal-title">@lang('Export Revenue Reports to Excel')</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>@lang('Start Date') <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control"
                                value="{{ request('start_date', date('Y-m-01')) }}" required>
                        </div>

                        <div class="form-group">
                            <label>@lang('End Date') <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control"
                                value="{{ request('end_date', date('Y-m-d')) }}" required>
                        </div>

                        <div class="form-group">
                            <label>@lang('Report Type')</label>
                            <select name="report_type" class="form-control">
                                <option value="">@lang('All Types')</option>
                                <option value="daily" {{ request('report_type') == 'daily' ? 'selected' : '' }}>
                                    @lang('Daily')</option>
                                <option value="weekly" {{ request('report_type') == 'weekly' ? 'selected' : '' }}>
                                    @lang('Weekly')</option>
                                <option value="monthly" {{ request('report_type') == 'monthly' ? 'selected' : '' }}>
                                    @lang('Monthly')</option>
                                <option value="custom" {{ request('report_type') == 'custom' ? 'selected' : '' }}>
                                    @lang('Custom')</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>@lang('Export Format') <span class="text-danger">*</span></label>
                            <select name="formatExport" class="form-control" required>
                                <option value="csv">@lang('Excel (CSV)')</option>
                                <option value="json">@lang('JSON')</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('Cancel')</button>
                        <button type="submit" class="btn btn-success">
                            <i class="las la-download"></i> @lang('Export')
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
