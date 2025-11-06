@extends('operator.layouts.app')

@section('panel')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">{{ $pageTitle }}</h4>
                        <a href="{{ route('operator.buses.seat-layouts.index', $bus) }}" class="btn btn-outline-secondary">
                            <i class="las la-arrow-left"></i> Back to Layouts
                        </a>
                    </div>
                    <div class="card-body">
                        <form id="seatLayoutForm" method="POST"
                            action="{{ route('operator.buses.seat-layouts.update', [$bus, $seatLayout]) }}">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <!-- Left Panel - Controls -->
                                <div class="col-md-3">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Layout Configuration</h5>
                                        </div>
                                        <div class="card-body">
                                            <!-- Basic Information -->
                                            <div class="mb-3">
                                                <label for="layout_name" class="form-label">Layout Name <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="layout_name"
                                                    name="layout_name"
                                                    value="{{ old('layout_name', $seatLayout->layout_name) }}" required>
                                                @error('layout_name')
                                                    <div class="text-danger small">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Deck Configuration -->
                                            <div class="mb-3">
                                                <label for="deck_type" class="form-label">Bus Type <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-control" id="deck_type" name="deck_type" required>
                                                    <option value="single"
                                                        {{ old('deck_type', $seatLayout->deck_type) == 'single' ? 'selected' : '' }}>
                                                        Single Decker</option>
                                                    <option value="double"
                                                        {{ old('deck_type', $seatLayout->deck_type) == 'double' ? 'selected' : '' }}>
                                                        Double Decker</option>
                                                </select>
                                                @error('deck_type')
                                                    <div class="text-danger small">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Seat Counts -->
                                            <div class="row">
                                                <div class="col-6">
                                                    <label for="upper_deck_seats" class="form-label">Upper Deck
                                                        Seats</label>
                                                    <input type="number" class="form-control" id="upper_deck_seats"
                                                        name="upper_deck_seats"
                                                        value="{{ old('upper_deck_seats', $seatLayout->upper_deck_seats) }}"
                                                        min="0" readonly>
                                                </div>
                                                <div class="col-6">
                                                    <label for="lower_deck_seats" class="form-label">Lower Deck
                                                        Seats</label>
                                                    <input type="number" class="form-control" id="lower_deck_seats"
                                                        name="lower_deck_seats"
                                                        value="{{ old('lower_deck_seats', $seatLayout->lower_deck_seats) }}"
                                                        min="0" readonly>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="total_seats" class="form-label">Total Seats</label>
                                                <input type="number" class="form-control" id="total_seats"
                                                    name="total_seats"
                                                    value="{{ old('total_seats', $seatLayout->total_seats) }}"
                                                    min="1" readonly>
                                            </div>

                                            <!-- Seat Types -->
                                            <div class="mb-4">
                                                <h6 class="mb-3">Seat Types</h6>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <div class="seat-type-item" data-type="nseat" data-category="seater">
                                                        <div class="seat-preview nseat"></div>
                                                        <small>Seater</small>
                                                    </div>
                                                    <div class="seat-type-item" data-type="hseat" data-category="sleeper">
                                                        <div class="seat-preview hseat"></div>
                                                        <small>Horizontal Sleeper</small>
                                                    </div>
                                                    <div class="seat-type-item" data-type="vseat" data-category="sleeper">
                                                        <div class="seat-preview vseat"></div>
                                                        <small>Vertical Sleeper</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Actions -->
                                            <div class="d-grid gap-2">
                                                <button type="button" class="btn btn-outline-primary" id="previewBtn">
                                                    <i class="las la-eye"></i> Preview Layout
                                                </button>
                                                <button type="button" class="btn btn-outline-warning" id="clearBtn">
                                                    <i class="las la-trash"></i> Clear All
                                                </button>
                                                <button type="submit" class="btn btn-success">
                                                    <i class="las la-save"></i> Update Layout
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Panel - Layout Editor with Inline Properties -->
                                <div class="col-md-9">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="card-title mb-0">Seat Layout Editor</h5>
                                                <small class="text-muted">Drag seat types from the left panel to create your
                                                    layout</small>
                                            </div>
                                            <!-- Inline Seat Properties Panel -->
                                            <div id="seatPropertiesPanel" style="display: none;" class="d-flex align-items-center gap-2">
                                                <div class="input-group input-group-sm" style="width: auto;">
                                                    <span class="input-group-text">Seat ID</span>
                                                    <input type="text" class="form-control form-control-sm" id="seatId" readonly style="width: 80px;">
                                                </div>
                                                <div class="input-group input-group-sm" style="width: auto;">
                                                    <span class="input-group-text">Price (₹)</span>
                                                    <input type="number" class="form-control form-control-sm" id="seatPrice" step="0.01" min="0" style="width: 100px;">
                                                </div>
                                                <div class="input-group input-group-sm" style="width: auto;">
                                                    <span class="input-group-text">Type</span>
                                                    <select class="form-select form-select-sm" id="seatType" style="width: auto;">
                                                        <option value="nseat">Seater</option>
                                                        <option value="hseat">Horizontal Sleeper</option>
                                                        <option value="vseat">Vertical Sleeper</option>
                                                    </select>
                                                </div>
                                                <button type="button" class="btn btn-primary btn-sm" id="updateSeatBtn">
                                                    <i class="las la-save"></i> Update
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-sm" id="deleteSeatBtn">
                                                    <i class="las la-trash"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body p-0">
                                            <div id="layoutEditor" class="layout-editor">
                                                <!-- Upper Deck (for double decker) -->
                                                <div class="deck-section" id="upperDeckSection">
                                                    <div class="deck-label">Upper Deck</div>
                                                    <div class="deck-container" id="upperDeck">
                                                        <div class="outerseat">
                                                            <div class="busSeatlft">
                                                                <div class="lower"></div>
                                                            </div>
                                                            <div class="busSeatrgt">
                                                                <div class="busSeat">
                                                                    <div class="seatcontainer clearfix"
                                                                        id="upperDeckGrid"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Lower Deck (always visible) -->
                                                <div class="deck-section">
                                                    <div class="deck-label" id="lowerDeckLabel">Lower Deck</div>
                                                    <div class="deck-container" id="lowerDeck">
                                                        <div class="outerseat">
                                                            <div class="busSeatlft">
                                                                <div class="lower"></div>
                                                            </div>
                                                            <div class="busSeatrgt">
                                                                <div class="busSeat">
                                                                    <div class="seatcontainer clearfix"
                                                                        id="lowerDeckGrid"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden input for layout data -->
                            <input type="hidden" name="layout_data" id="layoutData"
                                value="{{ old('layout_data', json_encode($seatLayout->layout_data)) }}">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Layout Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="previewContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .seat-type-item {
            text-align: center;
            cursor: grab;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.3s ease;
            user-select: none;
        }

        .seat-type-item:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
            transform: scale(1.05);
        }

        .seat-type-item:active {
            cursor: grabbing;
        }

        .seat-type-item.selected {
            border-color: #007bff;
            background-color: #e7f3ff;
        }

        .seat-type-item.dragging {
            opacity: 0.5;
            transform: rotate(5deg);
        }

        .seat-preview {
            width: 40px;
            height: 30px;
            margin: 0 auto 5px;
            border: 2px solid #333;
            border-radius: 4px;
            position: relative;
        }

        .seat-preview.nseat {
            background-color: #fff;
            border-color: #666;
        }

        .seat-preview.hseat {
            background-color: #e3f2fd;
            border-color: #1976d2;
            width: 50px;
        }

        .seat-preview.vseat {
            background-color: #f3e5f5;
            border-color: #7b1fa2;
            height: 40px;
        }

        .layout-editor {
            min-height: 600px;
            background-color: #f8f9fa;
            padding: 20px;
        }

        .deck-section {
            margin-bottom: 30px;
        }

        .deck-label {
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: #495057;
        }

        .deck-container {
            background-color: #fff;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            min-height: 250px;
            position: relative;
            overflow: visible;
        }

        .deck-grid {
            position: relative;
            width: 100%;
            min-height: 250px;
            height: auto;
        }

        .seat-item {
            position: absolute;
            cursor: pointer;
            border: 2px solid #333;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            transition: all 0.2s ease;
            user-select: none;
            margin: 4px;
        }

        .seat-item:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .seat-item.selected {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        }

        .seat-item.nseat {
            width: 30px;
            height: 25px;
            background-color: #fff;
            border-color: #666;
            color: #333;
            box-sizing: border-box;
        }

        .seat-item.hseat {
            width: 40px;
            height: 25px;
            background-color: #e3f2fd;
            border-color: #1976d2;
            color: #1976d2;
            box-sizing: border-box;
        }

        .seat-item.vseat {
            width: 25px;
            height: 35px;
            background-color: #f3e5f5;
            border-color: #7b1fa2;
            color: #7b1fa2;
            box-sizing: border-box;
        }

        .drag-over {
            background-color: #e7f3ff !important;
            border-color: #007bff !important;
        }

        .grid-snap {
            position: absolute;
            width: 5px;
            height: 5px;
            background-color: #ccc;
            border-radius: 50%;
            pointer-events: none;
        }

        /* Bus Seat Structure CSS */
        .outerseat,
        .outerlowerseat {
            display: flex;
            width: 100%;
            min-height: 250px;
            height: auto;
        }

        .busSeatlft {
            width: 80px;
            background-color: #f8f9fa;
            border-right: 2px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            min-height: 250px;
            height: auto;
        }

        .busSeatlft .lower {
            width: 40px;
            height: 40px;
            background-color: #6c757d;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        .busSeatlft .lower::after {
            content: "DRIVER";
            font-size: 8px;
        }

        .busSeatrgt {
            flex: 1;
            position: relative;
            min-height: 250px;
            height: auto;
        }

        .busSeat {
            width: 100%;
            min-height: 250px;
            height: auto;
            position: relative;
        }

        .seatcontainer {
            position: relative;
            width: 100%;
            min-height: 250px;
            height: auto;
            padding: 10px;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
@endpush

@push('script')
    <script src="{{ asset('assets/admin/js/seat-layout-editor.js') }}?v={{ time() }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing seat layout editor...');

            // Check if SeatLayoutEditor class exists
            if (typeof SeatLayoutEditor === 'undefined') {
                console.error('SeatLayoutEditor class not found!');
                alert('Seat layout editor failed to load. Please refresh the page.');
                return;
            }

            // Initialize the seat layout editor
            const editor = new SeatLayoutEditor({
                upperDeckGrid: document.getElementById('upperDeckGrid'),
                lowerDeckGrid: document.getElementById('lowerDeckGrid'),
                layoutDataInput: document.getElementById('layoutData'),
                totalSeatsInput: document.getElementById('total_seats'),
                upperDeckSeatsInput: document.getElementById('upper_deck_seats'),
                lowerDeckSeatsInput: document.getElementById('lower_deck_seats'),
                seatPropertiesPanel: document.getElementById('seatPropertiesPanel'),
                seatIdInput: document.getElementById('seatId'),
                seatPriceInput: document.getElementById('seatPrice'),
                seatTypeSelect: document.getElementById('seatType'),
                updateSeatBtn: document.getElementById('updateSeatBtn'),
                deleteSeatBtn: document.getElementById('deleteSeatBtn'),
                previewBtn: document.getElementById('previewBtn'),
                clearBtn: document.getElementById('clearBtn'),
                previewModal: document.getElementById('previewModal'),
                previewContent: document.getElementById('previewContent'),
                previewUrl: '{{ route('operator.buses.seat-layouts.preview', $bus) }}',
                deckTypeSelect: document.getElementById('deck_type'),
                upperDeckSection: document.getElementById('upperDeckSection'),
                lowerDeckLabel: document.getElementById('lowerDeckLabel')
            });

            // Handle deck type change
            document.getElementById('deck_type').addEventListener('change', function() {
                const deckType = this.value;
                const upperDeckSection = document.getElementById('upperDeckSection');
                const lowerDeckLabel = document.getElementById('lowerDeckLabel');

                if (deckType === 'single') {
                    upperDeckSection.style.display = 'none';
                    lowerDeckLabel.textContent = 'Main Deck';
                    editor.setDeckType('single');
                } else {
                    upperDeckSection.style.display = 'block';
                    lowerDeckLabel.textContent = 'Lower Deck';
                    editor.setDeckType('double');
                }
            });

            // Initialize deck type on page load (skip data clear during initial load)
            const initialDeckType = document.getElementById('deck_type').value;
            if (initialDeckType === 'single') {
                document.getElementById('upperDeckSection').style.display = 'none';
                document.getElementById('lowerDeckLabel').textContent = 'Main Deck';
                editor.setDeckType('single', true); // Skip data clear during initial load
            } else {
                editor.setDeckType('double', true); // Skip data clear during initial load
            }

            // Handle form submission
            document.getElementById('seatLayoutForm').addEventListener('submit', function(e) {
                const layoutData = editor.getLayoutData();
                if (Object.keys(layoutData).length === 0 ||
                    (!layoutData.upper_deck && !layoutData.lower_deck)) {
                    e.preventDefault();
                    alert('Please create at least one seat in your layout before saving.');
                    return false;
                }
            });
        });
    </script>
@endpush
