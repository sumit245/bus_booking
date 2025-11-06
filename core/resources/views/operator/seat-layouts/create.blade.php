@extends('operator.layouts.app')

@push('style')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('panel')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form id="seatLayoutForm" method="POST" action="{{ route('operator.buses.seat-layouts.store', $bus) }}">
                        @csrf

                        <div class="row">
                            <!-- Left Panel - Controls -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Layout Configuration</h5>
                                    </div>
                                    <div class="card-body">
                                        <!-- Basic Information -->
                                        <div class="mb-3">
                                            <label for="layout_name" class="form-label">Layout Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="layout_name" name="layout_name"
                                                value="{{ old('layout_name') }}" required>
                                            @error('layout_name')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Deck Configuration -->
                                        <div class="mb-3">
                                            <label for="deck_type" class="form-label">Bus Type <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control" id="deck_type" name="deck_type" required>
                                                <option value="single" {{ old('deck_type') == 'single' ? 'selected' : '' }}>
                                                    Single Decker
                                                </option>
                                                <option value="double" {{ old('deck_type') == 'double' ? 'selected' : '' }}>
                                                    Double Decker
                                                </option>
                                            </select>
                                            @error('deck_type')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Seat Layout Configuration -->
                                        <div class="mb-3">
                                            <label for="seat_layout" class="form-label">Seat Layout <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control" id="seat_layout" name="seat_layout" required>
                                                <option value="2x1" {{ old('seat_layout') == '2x1' ? 'selected' : '' }}>
                                                    2x1 (2 seats
                                                    left, 1 seat right of aisle)</option>
                                                <option value="2x2" {{ old('seat_layout') == '2x2' ? 'selected' : '' }}>
                                                    2x2 (2 seats
                                                    left, 2 seats right of aisle)</option>
                                                <option value="2x3" {{ old('seat_layout') == '2x3' ? 'selected' : '' }}>
                                                    2x3 (2 seats
                                                    left, 3 seats right of aisle)</option>
                                                <option value="3x2" {{ old('seat_layout') == '3x2' ? 'selected' : '' }}>
                                                    3x2 (3 seats
                                                    left, 2 seats right of aisle)</option>
                                                <option value="3x3" {{ old('seat_layout') == '3x3' ? 'selected' : '' }}>
                                                    3x3 (3 seats
                                                    left, 3 seats right of aisle)</option>
                                                <option value="custom"
                                                    {{ old('seat_layout') == 'custom' ? 'selected' : '' }}>Custom
                                                    Layout</option>
                                            </select>
                                            @error('seat_layout')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">NxM means N seats on left side, M seats on right
                                                side of aisle</small>
                                        </div>

                                        <!-- Columns Configuration -->
                                        <div class="mb-3">
                                            <label for="columns_per_row" class="form-label">Columns per Row <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="columns_per_row"
                                                name="columns_per_row" value="{{ old('columns_per_row', 10) }}"
                                                min="4" max="20" required>
                                            @error('columns_per_row')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Total number of columns (seats + aisles) per
                                                row</small>
                                        </div>

                                        <!-- Seat Counts -->
                                        <div class="row">
                                            <div class="col-6">
                                                <label for="upper_deck_seats" class="form-label">Upper Deck
                                                    Seats</label>
                                                <input type="number" class="form-control" id="upper_deck_seats"
                                                    name="upper_deck_seats" value="{{ old('upper_deck_seats', 0) }}"
                                                    min="0" readonly>
                                            </div>
                                            <div class="col-6">
                                                <label for="lower_deck_seats" class="form-label">Lower Deck
                                                    Seats</label>
                                                <input type="number" class="form-control" id="lower_deck_seats"
                                                    name="lower_deck_seats" value="{{ old('lower_deck_seats', 0) }}"
                                                    min="0" readonly>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="total_seats" class="form-label">Total Seats</label>
                                            <input type="number" class="form-control" id="total_seats" name="total_seats"
                                                value="{{ old('total_seats', 0) }}" min="1" readonly>
                                        </div>

                                        <!-- Seat Types -->
                                        <div class="mb-4">
                                            <h6 class="mb-3">Seat Types</h6>
                                            <div class="d-flex flex-wrap gap-1">
                                                <div class="seat-type-item" data-type="nseat" data-category="seater">
                                                    <div class="seat-preview nseat"></div>
                                                    <small>Seater</small>
                                                </div>
                                                <div class="seat-type-item" data-type="hseat" data-category="sleeper">
                                                    <div class="seat-preview hseat"></div>
                                                    <small>Hl Sleeper</small>
                                                </div>
                                                <div class="seat-type-item" data-type="vseat" data-category="sleeper">
                                                    <div class="seat-preview vseat"></div>
                                                    <small>Vl Sleeper</small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="d-grid gap-2">
                                            <button type="button" class="btn btn-outline-info" id="testBtn">
                                                <i class="las la-bug"></i> Test Drag & Drop
                                            </button>
                                            <button type="button" class="btn btn-outline-primary" id="previewBtn">
                                                <i class="las la-eye"></i> Preview Layout
                                            </button>
                                            <button type="button" class="btn btn-outline-warning" id="clearBtn">
                                                <i class="las la-trash"></i> Clear All
                                            </button>
                                            <button type="submit" class="btn btn-success">
                                                <i class="las la-save"></i> Save Layout
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Seat Properties Panel -->
                                <div class="card mt-3" id="seatPropertiesPanel" style="display: none;">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">Seat Properties</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="seatId" class="form-label">Seat ID</label>
                                            <input type="text" class="form-control" id="seatId" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="seatPrice" class="form-label">Price (₹)</label>
                                            <input type="number" class="form-control" id="seatPrice" step="0.01"
                                                min="0">
                                        </div>
                                        <div class="mb-3">
                                            <label for="seatType" class="form-label">Seat Type</label>
                                            <select class="form-control" id="seatType">
                                                <option value="nseat">Seater</option>
                                                <option value="hseat">Horizontal Sleeper</option>
                                                <option value="vseat">Vertical Sleeper</option>
                                            </select>
                                        </div>
                                        <div class="d-grid">
                                            <button type="button" class="btn btn-primary" id="updateSeatBtn">Update
                                                Seat</button>
                                            <button type="button" class="btn btn-outline-danger"
                                                id="deleteSeatBtn">Delete Seat</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Panel - Layout Editor -->
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Seat Layout Editor</h5>
                                        <small class="text-muted">
                                            <strong>Instructions:</strong>
                                            <ul>
                                                <li class="list-type-none">1. Select bus type (Single/Double Decker)
                                                </li>
                                                <li class="list-type-none">2. Drag seat types from the left panel to
                                                    the
                                                    deck areas below</li>
                                                <li class="list-type-none">3. Click on placed seats to edit their
                                                    properties</li>
                                                <li class="list-type-none">4. Use Preview to see the generated layout
                                                </li>
                                            </ul>
                                        </small>
                                    </div>
                                    <div class="card-body p-0">
                                        <div id="layoutEditor" class="layout-editor">
                                            <!-- Upper Deck (for double decker) -->
                                            <div class="deck-section" id="upperDeckSection">
                                                <div class="deck-label">Upper Deck</div>
                                                <div class="deck-container" id="upperDeck">
                                                    <div id="upperDeckGrid" class="deck-grid">
                                                        <!-- Grid will be generated by JavaScript -->
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Lower Deck (always visible) -->
                                            <div class="deck-section">
                                                <div class="deck-label" id="lowerDeckLabel">Main Deck</div>
                                                <div class="deck-container" id="lowerDeck">
                                                    <div id="lowerDeckGrid" class="deck-grid">
                                                        <!-- Grid will be generated by JavaScript -->
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
                            value="{{ old('layout_data', '{}') }}">
                    </form>
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
            margin: 4px;
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
            width: 45px;
            height: 30px;
        }

        .seat-preview.vseat {
            background-color: #f3e5f5;
            border-color: #7b1fa2;
            height: 45px;
            width: 30px;
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
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            min-height: 250px;
            position: relative;
            overflow: visible;
            transition: all 0.3s ease;
        }

        .deck-container:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }

        .deck-grid {
            position: relative;
            width: 100%;
            height: 100%;
            min-height: 250px;
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
        }

        .seat-item.hseat {
            width: 40px;
            height: 25px;
            background-color: #e3f2fd;
            border-color: #1976d2;
            color: #1976d2;
        }

        .seat-item.vseat {
            width: 25px;
            height: 35px;
            background-color: #f3e5f5;
            border-color: #7b1fa2;
            color: #7b1fa2;
        }

        /* Simple Grid System CSS */
        .seat-grid-container {
            position: relative;
            border: 2px solid #ddd;
            background-color: #f9f9f9;
        }

        .grid-cell {
            position: absolute;
            border: 1px solid #eee;
            background-color: #f9f9f9;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #999;
            transition: background-color 0.2s;
        }

        .grid-cell:hover {
            background-color: #e9ecef;
        }

        .aisle-line {
            position: absolute;
            background-color: #007bff;
            z-index: 10;
        }

        .aisle-label {
            position: absolute;
            background-color: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            z-index: 11;
        }

        /* Seat Position Styling */
        .seat-position {
            position: absolute;
            border: 1px dashed #ccc;
            background-color: rgba(0, 123, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #666;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .seat-position:hover {
            background-color: rgba(0, 123, 255, 0.2);
        }

        /* Bus Structure CSS */
        .outerseat,
        .outerlowerseat {
            display: flex;
            width: 100%;
            min-height: 250px;
            height: auto;
        }

        .busSeatlft {
            width: 80px;
            min-height: 250px;
            height: auto;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #666;
        }

        .busSeatrgt {
            flex: 1;
            min-height: 250px;
            height: auto;
            position: relative;
        }

        .busSeat {
            width: 100%;
            min-height: 250px;
            height: auto;
            position: relative;
        }

        .seatcontainer {
            width: 100%;
            min-height: 250px;
            height: auto;
            position: relative;
        }

        .aisle-row {
            position: absolute;
            background-color: #e7f3ff;
            border: 2px solid #007bff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
            color: #007bff;
            z-index: 10;
        }

        .deck-grid {
            min-height: 250px;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            height: auto;
        }

        /* Make the bus structure fit content */
        .outerseat,
        .outerlowerseat {
            display: flex;
            width: 100%;
            min-height: 250px;
            height: auto;
        }

        .busSeatrgt {
            flex: 1;
            min-height: 250px;
            height: auto;
            position: relative;
        }

        .seatcontainer {
            width: 100%;
            min-height: 250px;
            height: auto;
            position: relative;
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

        /* Legend */
        .legend {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(255, 255, 255, 0.9);
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }

        .legend-color {
            width: 20px;
            height: 15px;
            margin-right: 8px;
            border: 1px solid #333;
            border-radius: 2px;
        }

        .drop-zone-placeholder {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: #6c757d;
            pointer-events: none;
        }

        .drop-zone-placeholder p {
            margin: 10px 0 0 0;
            font-size: 14px;
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

        /* Bus Layout Positions */
        .seat-position {
            position: absolute;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .seat-position:hover {
            background-color: #f0f8ff;
            border-color: #007bff;
        }

        .seat-position.drag-over {
            background-color: #e7f3ff !important;
            border-color: #007bff !important;
            transform: scale(1.05);
        }

        .aisle-position {
            position: absolute;
            border: 1px solid #ccc;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: not-allowed;
        }

        .seat-placeholder {
            font-size: 20px;
            color: #ccc;
            font-weight: bold;
        }

        .aisle-placeholder {
            font-size: 10px;
            color: #999;
            font-weight: bold;
        }

        /* Seat Items */
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
            width: 100%;
            height: 100%;
        }

        .seat-item:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .seat-item.selected {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        }

        .seat-item.dragging {
            opacity: 0.7;
            transform: rotate(5deg);
        }

        .seat-item.nseat {
            background-color: #fff;
            border-color: #666;
            color: #333;
        }

        .seat-item.hseat {
            background-color: #e3f2fd;
            border-color: #1976d2;
            color: #1976d2;
        }

        .seat-item.vseat {
            background-color: #f3e5f5;
            border-color: #7b1fa2;
            color: #7b1fa2;
        }
    </style>
@endpush

@push('script')
    <script src="{{ asset('assets/admin/js/seat-layout-editor.js') }}"></script>
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
                seatLayoutSelect: document.getElementById('seat_layout'),
                columnsPerRowInput: document.getElementById('columns_per_row'),
                upperDeckSection: document.getElementById('upperDeckSection'),
                lowerDeckLabel: document.getElementById('lowerDeckLabel')
            });

            // Handle deck type change
            document.getElementById('deck_type').addEventListener('change', function() {
                const deckType = this.value;
                const upperDeckSection = document.getElementById('upperDeckSection');
                const lowerDeckLabel = document.getElementById('lowerDeckLabel');

                console.log('Deck type changed to:', deckType);

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

            // Test button functionality
            document.getElementById('testBtn').addEventListener('click', function() {
                console.log('Test button clicked');

                // Test adding a seat programmatically
                const testSeat = {
                    type: 'nseat',
                    category: 'seater'
                };

                // Add a test seat to lower deck
                editor.addSeat('lower_deck', 30, 30, testSeat.type, testSeat.category);

                alert('Test seat added! Check the lower deck area.');
            });

            // Initialize deck type on page load
            const initialDeckType = document.getElementById('deck_type').value;
            console.log('Initial deck type:', initialDeckType);

            if (initialDeckType === 'single') {
                document.getElementById('upperDeckSection').style.display = 'none';
                document.getElementById('lowerDeckLabel').textContent = 'Main Deck';
                editor.setDeckType('single');
            } else {
                editor.setDeckType('double');
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

@push('breadcrumb-plugins')
    <a href="{{ route('operator.buses.seat-layouts.index', $bus) }}"
        class="btn btn-sm btn--primary box--shadow1 text--small">
        <i class="las la-angle-double-left"></i>@lang('Go Back')
    </a>
@endpush
