@extends('operator.layouts.app')

@section('panel')
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="las la-ban"></i>
                    Cancellation Policy Management - {{ $bus->travel_name }}
                </h5>
                <p class="text-muted mb-0">Configure cancellation charges and policies for your bus</p>
            </div>
            <div class="card-body">
                <!-- Policy Type Selection -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="form-label">Policy Type</label>
                            <div class="radio-group">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="policy_type" id="default_policy"
                                           value="default" {{ $bus->use_default_cancellation_policy ? 'checked' : '' }}>
                                    <label class="form-check-label" for="default_policy">
                                        <strong>Use Default Policy</strong>
                                        <small class="text-muted d-block">Standard cancellation charges</small>
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="policy_type" id="custom_policy"
                                           value="custom" {{ !$bus->use_default_cancellation_policy ? 'checked' : '' }}>
                                    <label class="form-check-label" for="custom_policy">
                                        <strong>Custom Policy</strong>
                                        <small class="text-muted d-block">Configure your own charges</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <form action="{{ route('operator.buses.cancellation-policy.update', $bus->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Default Policy Preview -->
                    <div id="default-policy-section" style="display: {{ $bus->use_default_cancellation_policy ? 'block' : 'none' }};">
                        <div class="alert alert-info">
                            <h6><i class="las la-info-circle"></i> Default Cancellation Policy</h6>
                            <p class="mb-0">These are the standard charges applied automatically:</p>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Time Before Departure</th>
                                        <th>Charge Type</th>
                                        <th>Charge Amount</th>
                                        <th>Policy Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>More than 24 hours</td>
                                        <td><span class="badge badge-primary">Fixed Amount</span></td>
                                        <td>₹100</td>
                                        <td>Minimal cancellation fee</td>
                                    </tr>
                                    <tr>
                                        <td>24 to 12 hours</td>
                                        <td><span class="badge badge-warning">Percentage</span></td>
                                        <td>25%</td>
                                        <td>Standard cancellation charge</td>
                                    </tr>
                                    <tr>
                                        <td>12 to 6 hours</td>
                                        <td><span class="badge badge-warning">Percentage</span></td>
                                        <td>50%</td>
                                        <td>Higher charge for late cancellation</td>
                                    </tr>
                                    <tr>
                                        <td>6 to 2 hours</td>
                                        <td><span class="badge badge-warning">Percentage</span></td>
                                        <td>75%</td>
                                        <td>Very late cancellation</td>
                                    </tr>
                                    <tr>
                                        <td>Less than 2 hours</td>
                                        <td><span class="badge badge-danger">Percentage</span></td>
                                        <td>90%</td>
                                        <td>Last minute cancellation</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Custom Policy Configuration -->
                    <div id="custom-policy-section" style="display: {{ !$bus->use_default_cancellation_policy ? 'block' : 'none' }};">
                        <div class="alert alert-warning">
                            <h6><i class="las la-exclamation-triangle"></i> Custom Cancellation Policy</h6>
                            <p class="mb-0">Configure your own cancellation charges. Make sure to cover all time periods.</p>
                        </div>

                        <div id="policy-rules">
                            @if($bus->cancellation_policy)
                                @foreach($bus->cancellation_policy as $index => $policy)
                                <div class="policy-rule border rounded p-3 mb-3" data-rule-index="{{ $index }}">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">Policy Rule {{ $index + 1 }}</h6>
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-rule" onclick="removeRule({{ $index }})">
                                            <i class="las la-trash"></i> Remove
                                        </button>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Time Range</label>
                                                <div class="input-group">
                                                    <input type="number" name="policies[{{ $index }}][time_from]"
                                                           class="form-control" placeholder="From"
                                                           value="{{ explode('$', $policy['TimeBeforeDept'])[0] ?? '' }}" required>
                                                    <span class="input-group-text">to</span>
                                                    <input type="number" name="policies[{{ $index }}][time_to]"
                                                           class="form-control" placeholder="To"
                                                           value="{{ explode('$', $policy['TimeBeforeDept'])[1] ?? '' }}" required>
                                                </div>
                                                <small class="text-muted">Hours before departure</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Charge Type</label>
                                                <select name="policies[{{ $index }}][charge_type]" class="form-control" required>
                                                    <option value="1" {{ $policy['CancellationChargeType'] == 1 ? 'selected' : '' }}>Fixed Amount (₹)</option>
                                                    <option value="2" {{ $policy['CancellationChargeType'] == 2 ? 'selected' : '' }}>Percentage (%)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Charge Amount</label>
                                                <input type="number" name="policies[{{ $index }}][charge]"
                                                       class="form-control" placeholder="Amount"
                                                       value="{{ $policy['CancellationCharge'] }}" required step="0.01" min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Description</label>
                                                <input type="text" name="policies[{{ $index }}][description]"
                                                       class="form-control" placeholder="Policy description"
                                                       value="{{ $policy['PolicyString'] }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            @else
                                <div class="policy-rule border rounded p-3 mb-3" data-rule-index="0">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">Policy Rule 1</h6>
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-rule" onclick="removeRule(0)">
                                            <i class="las la-trash"></i> Remove
                                        </button>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Time Range</label>
                                                <div class="input-group">
                                                    <input type="number" name="policies[0][time_from]" class="form-control" placeholder="From" required>
                                                    <span class="input-group-text">to</span>
                                                    <input type="number" name="policies[0][time_to]" class="form-control" placeholder="To" required>
                                                </div>
                                                <small class="text-muted">Hours before departure</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Charge Type</label>
                                                <select name="policies[0][charge_type]" class="form-control" required>
                                                    <option value="">Select Type</option>
                                                    <option value="1">Fixed Amount (₹)</option>
                                                    <option value="2">Percentage (%)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Charge Amount</label>
                                                <input type="number" name="policies[0][charge]" class="form-control" placeholder="Amount" required step="0.01" min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Description</label>
                                                <input type="text" name="policies[0][description]" class="form-control" placeholder="Policy description">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-primary" onclick="addRule()">
                                <i class="las la-plus"></i> Add New Rule
                            </button>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('operator.buses.show', $bus->id) }}" class="btn btn-outline-secondary">
                            <i class="las la-arrow-left"></i> Back to Bus Details
                        </a>
                        <div>
                            <button type="button" class="btn btn-outline-info" onclick="previewPolicy()">
                                <i class="las la-eye"></i> Preview Policy
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="las la-save"></i> Save Cancellation Policy
                            </button>
                        </div>
                    </div>

                    <input type="hidden" name="use_default_policy" id="use_default_policy_input" value="{{ $bus->use_default_cancellation_policy ? '1' : '0' }}">
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancellation Policy Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="preview-content">
                    <!-- Policy preview will be generated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    let ruleIndex = {{ $bus->cancellation_policy ? count($bus->cancellation_policy) : 1 }};

    // Handle policy type change
    document.querySelectorAll('input[name="policy_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const useDefault = this.value === 'default';
            document.getElementById('use_default_policy_input').value = useDefault ? '1' : '0';

            document.getElementById('default-policy-section').style.display = useDefault ? 'block' : 'none';
            document.getElementById('custom-policy-section').style.display = useDefault ? 'none' : 'block';
        });
    });

    function addRule() {
        const container = document.getElementById('policy-rules');
        const ruleHtml = `
            <div class="policy-rule border rounded p-3 mb-3" data-rule-index="${ruleIndex}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Policy Rule ${ruleIndex + 1}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-rule" onclick="removeRule(${ruleIndex})">
                        <i class="las la-trash"></i> Remove
                    </button>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Time Range</label>
                            <div class="input-group">
                                <input type="number" name="policies[${ruleIndex}][time_from]" class="form-control" placeholder="From" required>
                                <span class="input-group-text">to</span>
                                <input type="number" name="policies[${ruleIndex}][time_to]" class="form-control" placeholder="To" required>
                            </div>
                            <small class="text-muted">Hours before departure</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Charge Type</label>
                            <select name="policies[${ruleIndex}][charge_type]" class="form-control" required>
                                <option value="">Select Type</option>
                                <option value="1">Fixed Amount (₹)</option>
                                <option value="2">Percentage (%)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Charge Amount</label>
                            <input type="number" name="policies[${ruleIndex}][charge]" class="form-control" placeholder="Amount" required step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Description</label>
                            <input type="text" name="policies[${ruleIndex}][description]" class="form-control" placeholder="Policy description">
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', ruleHtml);
        ruleIndex++;
    }

    function removeRule(index) {
        const rule = document.querySelector(`[data-rule-index="${index}"]`);
        if (rule && document.querySelectorAll('.policy-rule').length > 1) {
            rule.remove();
        } else {
            alert('At least one policy rule is required.');
        }
    }

    function previewPolicy() {
        const useDefault = document.getElementById('use_default_policy_input').value === '1';
        const previewContent = document.getElementById('preview-content');

        if (useDefault) {
            previewContent.innerHTML = `
                <h6>Default Cancellation Policy</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <tr><td>More than 24 hours</td><td>₹100 (Fixed)</td></tr>
                        <tr><td>24 to 12 hours</td><td>25% of ticket price</td></tr>
                        <tr><td>12 to 6 hours</td><td>50% of ticket price</td></tr>
                        <tr><td>6 to 2 hours</td><td>75% of ticket price</td></tr>
                        <tr><td>Less than 2 hours</td><td>90% of ticket price</td></tr>
                    </table>
                </div>
            `;
        } else {
            let policyHtml = '<h6>Custom Cancellation Policy</h6><div class="table-responsive"><table class="table table-sm table-bordered"><tr><th>Time Range</th><th>Charge</th><th>Description</th></tr>';

            document.querySelectorAll('.policy-rule').forEach((rule, index) => {
                const timeFrom = rule.querySelector(`input[name="policies[${rule.dataset.ruleIndex}][time_from]"]`).value;
                const timeTo = rule.querySelector(`input[name="policies[${rule.dataset.ruleIndex}][time_to]"]`).value;
                const chargeType = rule.querySelector(`select[name="policies[${rule.dataset.ruleIndex}][charge_type]"]`).value;
                const charge = rule.querySelector(`input[name="policies[${rule.dataset.ruleIndex}][charge]"]`).value;
                const description = rule.querySelector(`input[name="policies[${rule.dataset.ruleIndex}][description]"]`).value;

                if (timeFrom && timeTo && chargeType && charge) {
                    const chargeDisplay = chargeType === '1' ? `₹${charge}` : `${charge}%`;
                    policyHtml += `<tr><td>${timeFrom} to ${timeTo} hours</td><td>${chargeDisplay}</td><td>${description || 'N/A'}</td></tr>`;
                }
            });

            policyHtml += '</table></div>';
            previewContent.innerHTML = policyHtml;
        }

        // Show modal (assuming Bootstrap 5)
        new bootstrap.Modal(document.getElementById('previewModal')).show();
    }

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const useDefault = document.getElementById('use_default_policy_input').value === '1';

        if (!useDefault) {
            const rules = document.querySelectorAll('.policy-rule');
            if (rules.length === 0) {
                e.preventDefault();
                alert('Please add at least one cancellation policy rule.');
                return;
            }

            // Validate overlapping time ranges
            const timeRanges = [];
            let hasError = false;

            rules.forEach(rule => {
                const timeFrom = parseInt(rule.querySelector(`input[name*="[time_from]"]`).value);
                const timeTo = parseInt(rule.querySelector(`input[name*="[time_to]"]`).value);

                if (timeFrom >= timeTo) {
                    hasError = true;
                    alert('Invalid time range: "From" time must be less than "To" time.');
                    return;
                }

                timeRanges.push([timeFrom, timeTo]);
            });

            if (hasError) {
                e.preventDefault();
            }
        }
    });
</script>
@endpush

@push('style')
<style>
    .policy-rule {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6 !important;
        transition: all 0.3s ease;
    }

    .policy-rule:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .radio-group .form-check {
        margin-right: 2rem;
    }

    .badge {
        font-size: 0.85em;
    }

    .table th {
        background-color: #6f42c1;
        color: white;
        font-weight: 600;
    }

    .input-group-text {
        background-color: #e9ecef;
        border-color: #ced4da;
    }

    .alert h6 {
        margin-bottom: 0.5rem;
    }

    .btn {
        transition: all 0.2s ease;
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
</style>
@endpush
