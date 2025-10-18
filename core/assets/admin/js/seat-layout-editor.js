/**
 * Seat Layout Editor - Drag and Drop Functionality
 * Handles the creation and management of bus seat layouts
 */
class SeatLayoutEditor {
    constructor(options) {
        this.upperDeckGrid = options.upperDeckGrid;
        this.lowerDeckGrid = options.lowerDeckGrid;
        this.layoutDataInput = options.layoutDataInput;
        this.totalSeatsInput = options.totalSeatsInput;
        this.upperDeckSeatsInput = options.upperDeckSeatsInput;
        this.lowerDeckSeatsInput = options.lowerDeckSeatsInput;
        this.seatPropertiesPanel = options.seatPropertiesPanel;
        this.seatIdInput = options.seatIdInput;
        this.seatPriceInput = options.seatPriceInput;
        this.seatTypeSelect = options.seatTypeSelect;
        this.updateSeatBtn = options.updateSeatBtn;
        this.deleteSeatBtn = options.deleteSeatBtn;
        this.previewBtn = options.previewBtn;
        this.clearBtn = options.clearBtn;
        this.previewModal = options.previewModal;
        this.previewContent = options.previewContent;
        this.previewUrl = options.previewUrl;
        this.deckTypeSelect = options.deckTypeSelect;
        this.upperDeckSection = options.upperDeckSection;
        this.lowerDeckLabel = options.lowerDeckLabel;

        this.selectedSeat = null;
        this.seatCounter = 1;
        this.deckType = 'double'; // Default to double decker
        this.layoutData = {
            upper_deck: { rows: {} },
            lower_deck: { rows: {} }
        };

        this.init();
    }

    init() {
        console.log('SeatLayoutEditor initialized');
        this.setupEventListeners();
        this.setupDragAndDrop();
        this.setupGridSnapping();
        this.loadExistingData();
        console.log('SeatLayoutEditor setup complete');
    }

    setupEventListeners() {
        // Seat type selection
        document.querySelectorAll('.seat-type-item').forEach(item => {
            item.addEventListener('click', (e) => {
                document.querySelectorAll('.seat-type-item').forEach(i => i.classList.remove('selected'));
                item.classList.add('selected');
            });
        });

        // Update seat button
        this.updateSeatBtn.addEventListener('click', () => this.updateSelectedSeat());

        // Delete seat button
        this.deleteSeatBtn.addEventListener('click', () => this.deleteSelectedSeat());

        // Preview button
        this.previewBtn.addEventListener('click', () => this.showPreview());

        // Clear button
        this.clearBtn.addEventListener('click', () => this.clearLayout());

        // Close properties panel when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.seatPropertiesPanel.contains(e.target) &&
                !e.target.closest('.seat-item')) {
                this.hideSeatProperties();
            }
        });
    }

    setupDragAndDrop() {
        console.log('Setting up drag and drop...');

        // Make seat type items draggable
        const seatTypeItems = document.querySelectorAll('.seat-type-item');
        console.log('Found seat type items:', seatTypeItems.length);

        seatTypeItems.forEach((item, index) => {
            item.draggable = true;
            console.log(`Making item ${index} draggable:`, item.dataset.type);

            item.addEventListener('dragstart', (e) => {
                const seatType = item.dataset.type;
                const category = item.dataset.category;
                e.dataTransfer.setData('text/plain', JSON.stringify({
                    type: seatType,
                    category: category
                }));
                item.classList.add('dragging');
                console.log('Drag started:', seatType, category);
            });

            item.addEventListener('dragend', (e) => {
                item.classList.remove('dragging');
                console.log('Drag ended');
            });
        });

        // Setup drop zones for both decks
        [this.upperDeckGrid, this.lowerDeckGrid].forEach(grid => {
            console.log('Setting up drop zone for:', grid.id);

            grid.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
                grid.classList.add('drag-over');
                console.log('Drag over:', grid.id);
            });

            grid.addEventListener('dragleave', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (!grid.contains(e.relatedTarget)) {
                    grid.classList.remove('drag-over');
                }
            });

            grid.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                grid.classList.remove('drag-over');

                try {
                    const data = JSON.parse(e.dataTransfer.getData('text/plain'));
                    const rect = grid.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;

                    const deck = grid.id === 'upperDeckGrid' ? 'upper_deck' : 'lower_deck';
                    console.log('Drop event:', deck, x, y, data);
                    this.addSeat(deck, x, y, data.type, data.category);
                } catch (error) {
                    console.error('Error parsing drop data:', error);
                }
            });
        });

        console.log('Drag and drop setup complete');
    }

    setupGridSnapping() {
        // Add grid snapping points (25px spacing for seats, 30px for rows)
        [this.upperDeckGrid, this.lowerDeckGrid].forEach(grid => {
            for (let x = 0; x < 500; x += 25) {
                for (let y = 0; y < 300; y += 30) {
                    const snapPoint = document.createElement('div');
                    snapPoint.className = 'grid-snap';
                    snapPoint.style.left = x + 'px';
                    snapPoint.style.top = y + 'px';
                    grid.appendChild(snapPoint);
                }
            }
        });
    }

    addSeat(deck, x, y, type, category) {
        // For single decker, only allow lower deck
        if (this.deckType === 'single' && deck === 'upper_deck') {
            console.log('Cannot add seats to upper deck in single decker bus');
            return;
        }

        // Account for the busSeatlft width (80px) and padding (10px)
        const adjustedX = x - 90; // 80px for busSeatlft + 10px padding

        // Check if position is within valid bounds
        if (adjustedX < 0) {
            console.log('Cannot place seat in driver area');
            return;
        }

        // Snap to grid (25px spacing like in the API)
        const snappedX = Math.round(adjustedX / 25) * 25;
        const snappedY = Math.round(y / 30) * 30;

        // Check for overlapping seats
        if (this.isSeatOverlapping(deck, snappedX, snappedY, type)) {
            console.log('Seat position overlaps with existing seat');
            return;
        }

        // Calculate row number (each 30px is a row)
        const rowNumber = Math.floor(snappedY / 30) + 1;

        // Generate seat ID
        const seatId = this.generateSeatId(deck, rowNumber);

        // Create seat data
        const seatData = {
            seat_id: seatId,
            type: type,
            category: category,
            price: 0,
            position: snappedY,
            left: snappedX,
            is_available: true,
            is_sleeper: category === 'sleeper'
        };

        // Add to layout data
        if (!this.layoutData[deck].rows[rowNumber]) {
            this.layoutData[deck].rows[rowNumber] = [];
        }
        this.layoutData[deck].rows[rowNumber].push(seatData);

        // Create visual seat element
        this.createSeatElement(deck, seatData);

        // Hide placeholder if it exists
        this.hidePlaceholder(deck);

        // Update counts
        this.updateSeatCounts();
        this.updateLayoutDataInput();

        console.log('Seat added:', seatData);
    }

    generateSeatId(deck, rowNumber) {
        const prefix = deck === 'upper_deck' ? 'U' : 'L';
        return `${prefix}${this.seatCounter++}`;
    }

    createSeatElement(deck, seatData) {
        const grid = deck === 'upper_deck' ? this.upperDeckGrid : this.lowerDeckGrid;

        const seatElement = document.createElement('div');
        seatElement.className = `seat-item ${seatData.type}`;
        seatElement.style.left = seatData.left + 'px';
        seatElement.style.top = seatData.position + 'px';
        seatElement.textContent = seatData.seat_id;
        seatElement.dataset.seatId = seatData.seat_id;
        seatElement.dataset.deck = deck;
        seatElement.dataset.seatData = JSON.stringify(seatData);

        // Handle seat click for editing/deletion
        seatElement.addEventListener('click', (e) => {
            e.stopPropagation();
            this.selectSeat(seatElement, seatData);
        });

        // Handle seat dragging
        seatElement.addEventListener('dragend', (e) => {
            const rect = grid.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            if (x >= 0 && y >= 0 && x <= rect.width && y <= rect.height) {
                this.moveSeat(seatData.seat_id, deck, x, y);
            }
        });

        grid.appendChild(seatElement);
    }

    selectSeat(seatElement, seatData) {
        // Remove previous selection
        document.querySelectorAll('.seat-item').forEach(seat => {
            seat.classList.remove('selected');
        });

        // Select current seat
        seatElement.classList.add('selected');
        this.selectedSeat = seatData;

        // Show properties panel
        this.showSeatProperties(seatData);
    }

    showSeatProperties(seatData) {
        this.seatIdInput.value = seatData.seat_id;
        this.seatPriceInput.value = seatData.price;
        this.seatTypeSelect.value = seatData.type;
        this.seatPropertiesPanel.style.display = 'block';
    }

    hideSeatProperties() {
        this.seatPropertiesPanel.style.display = 'none';
        this.selectedSeat = null;
        document.querySelectorAll('.seat-item').forEach(seat => {
            seat.classList.remove('selected');
        });
    }

    updateSelectedSeat() {
        if (!this.selectedSeat) return;

        const newPrice = parseFloat(this.seatPriceInput.value) || 0;
        const newType = this.seatTypeSelect.value;
        const newCategory = newType === 'nseat' ? 'seater' : 'sleeper';

        // Update seat data
        this.selectedSeat.price = newPrice;
        this.selectedSeat.type = newType;
        this.selectedSeat.category = newCategory;
        this.selectedSeat.is_sleeper = newCategory === 'sleeper';

        // Update visual element
        const seatElement = document.querySelector(`[data-seat-id="${this.selectedSeat.seat_id}"]`);
        if (seatElement) {
            seatElement.className = `seat-item ${newType}`;
        }

        // Update layout data
        this.updateLayoutDataInput();
    }

    deleteSelectedSeat() {
        if (!this.selectedSeat) return;

        if (confirm('Delete this seat?')) {
            // Remove from layout data
            const deck = this.selectedSeat.deck || 'upper_deck';
            const rowNumber = Math.floor(this.selectedSeat.position / 30) + 1;

            if (this.layoutData[deck].rows[rowNumber]) {
                this.layoutData[deck].rows[rowNumber] = this.layoutData[deck].rows[rowNumber]
                    .filter(seat => seat.seat_id !== this.selectedSeat.seat_id);

                // Remove empty rows
                if (this.layoutData[deck].rows[rowNumber].length === 0) {
                    delete this.layoutData[deck].rows[rowNumber];
                }
            }

            // Remove visual element
            const seatElement = document.querySelector(`[data-seat-id="${this.selectedSeat.seat_id}"]`);
            if (seatElement) {
                seatElement.remove();
            }

            // Update counts
            this.updateSeatCounts();
            this.updateLayoutDataInput();
            this.hideSeatProperties();
        }
    }

    moveSeat(seatId, deck, x, y) {
        // Snap to grid
        const snappedX = Math.round(x / 30) * 30;
        const snappedY = Math.round(y / 30) * 30;

        // Find seat in layout data
        let seatData = null;
        let oldRowNumber = null;

        for (const [rowNum, seats] of Object.entries(this.layoutData[deck].rows)) {
            const seatIndex = seats.findIndex(seat => seat.seat_id === seatId);
            if (seatIndex !== -1) {
                seatData = seats[seatIndex];
                oldRowNumber = parseInt(rowNum);
                break;
            }
        }

        if (!seatData) return;

        // Calculate new row number
        const newRowNumber = Math.floor(snappedY / 30) + 1;

        // Update seat data
        seatData.position = snappedY;
        seatData.left = snappedX;

        // Move to new row if necessary
        if (oldRowNumber !== newRowNumber) {
            // Remove from old row
            this.layoutData[deck].rows[oldRowNumber] = this.layoutData[deck].rows[oldRowNumber]
                .filter(seat => seat.seat_id !== seatId);

            // Remove empty rows
            if (this.layoutData[deck].rows[oldRowNumber].length === 0) {
                delete this.layoutData[deck].rows[oldRowNumber];
            }

            // Add to new row
            if (!this.layoutData[deck].rows[newRowNumber]) {
                this.layoutData[deck].rows[newRowNumber] = [];
            }
            this.layoutData[deck].rows[newRowNumber].push(seatData);
        }

        // Update visual element
        const seatElement = document.querySelector(`[data-seat-id="${seatId}"]`);
        if (seatElement) {
            seatElement.style.left = snappedX + 'px';
            seatElement.style.top = snappedY + 'px';
        }

        this.updateLayoutDataInput();
    }

    updateSeatCounts() {
        let upperCount = 0;
        let lowerCount = 0;

        // Count upper deck seats (only for double decker)
        if (this.deckType === 'double') {
            Object.values(this.layoutData.upper_deck.rows).forEach(seats => {
                upperCount += seats.length;
            });
        }

        // Count lower deck seats
        Object.values(this.layoutData.lower_deck.rows).forEach(seats => {
            lowerCount += seats.length;
        });

        this.upperDeckSeatsInput.value = upperCount;
        this.lowerDeckSeatsInput.value = lowerCount;
        this.totalSeatsInput.value = upperCount + lowerCount;
    }

    setDeckType(deckType) {
        this.deckType = deckType;

        // Clear upper deck data for single decker
        if (deckType === 'single') {
            this.layoutData.upper_deck = { rows: {} };
            // Clear upper deck visual elements
            this.upperDeckGrid.querySelectorAll('.seat-item').forEach(seat => seat.remove());
        }

        this.updateSeatCounts();
        this.updateLayoutDataInput();
    }

    hidePlaceholder(deck) {
        const grid = deck === 'upper_deck' ? this.upperDeckGrid : this.lowerDeckGrid;
        const placeholder = grid.querySelector('.drop-zone-placeholder');
        if (placeholder) {
            placeholder.style.display = 'none';
        }
    }

    isSeatOverlapping(deck, x, y, type) {
        const grid = deck === 'upper_deck' ? this.upperDeckGrid : this.lowerDeckGrid;
        const existingSeats = grid.querySelectorAll('.seat-item');

        // Get seat dimensions based on type
        const seatWidth = this.getSeatWidth(type);
        const seatHeight = this.getSeatHeight(type);

        for (let seat of existingSeats) {
            const seatX = parseInt(seat.style.left);
            const seatY = parseInt(seat.style.top);
            const existingType = seat.className.split(' ')[1]; // Get seat type from class
            const existingWidth = this.getSeatWidth(existingType);
            const existingHeight = this.getSeatHeight(existingType);

            // Check for overlap
            if (x < seatX + existingWidth && x + seatWidth > seatX &&
                y < seatY + existingHeight && y + seatHeight > seatY) {
                return true;
            }
        }

        return false;
    }

    getSeatWidth(type) {
        switch (type) {
            case 'hseat': return 40; // Horizontal sleeper
            case 'vseat': return 25; // Vertical sleeper
            case 'nseat':
            default: return 30; // Normal seater
        }
    }

    getSeatHeight(type) {
        switch (type) {
            case 'vseat': return 35; // Vertical sleeper
            case 'hseat':
            case 'nseat':
            default: return 25; // Normal seater and horizontal sleeper
        }
    }

    selectSeat(seatElement, seatData) {
        // Remove previous selection
        document.querySelectorAll('.seat-item.selected').forEach(seat => {
            seat.classList.remove('selected');
        });

        // Select current seat
        seatElement.classList.add('selected');
        this.selectedSeat = seatElement;

        // Show seat properties panel
        this.showSeatProperties(seatData);
    }

    showSeatProperties(seatData) {
        this.seatIdInput.value = seatData.seat_id;
        this.seatPriceInput.value = seatData.price;
        this.seatTypeSelect.value = seatData.type;
        this.seatPropertiesPanel.style.display = 'block';
    }

    hideSeatProperties() {
        this.seatPropertiesPanel.style.display = 'none';
        this.selectedSeat = null;
    }

    deleteSelectedSeat() {
        if (!this.selectedSeat) return;

        const seatId = this.selectedSeat.dataset.seatId;
        const deck = this.selectedSeat.dataset.deck;

        // Remove from layout data
        Object.keys(this.layoutData[deck].rows).forEach(rowNum => {
            this.layoutData[deck].rows[rowNum] = this.layoutData[deck].rows[rowNum].filter(
                seat => seat.seat_id !== seatId
            );
        });

        // Remove visual element
        this.selectedSeat.remove();

        // Hide properties panel
        this.hideSeatProperties();

        // Update counts
        this.updateSeatCounts();
        this.updateLayoutDataInput();

        console.log('Seat deleted:', seatId);
    }

    updateSelectedSeat() {
        if (!this.selectedSeat) return;

        const seatId = this.seatIdInput.value;
        const price = parseFloat(this.seatPriceInput.value) || 0;
        const type = this.seatTypeSelect.value;

        // Update visual element
        this.selectedSeat.textContent = seatId;
        this.selectedSeat.className = `seat-item ${type}`;
        this.selectedSeat.dataset.seatId = seatId;

        // Update layout data
        const deck = this.selectedSeat.dataset.deck;
        Object.keys(this.layoutData[deck].rows).forEach(rowNum => {
            this.layoutData[deck].rows[rowNum].forEach(seat => {
                if (seat.seat_id === this.selectedSeat.dataset.seatId) {
                    seat.seat_id = seatId;
                    seat.price = price;
                    seat.type = type;
                }
            });
        });

        // Update counts
        this.updateSeatCounts();
        this.updateLayoutDataInput();

        console.log('Seat updated:', { seatId, price, type });
    }

    updateLayoutDataInput() {
        this.layoutDataInput.value = JSON.stringify(this.layoutData);
    }

    clearLayout() {
        if (confirm('Clear the entire layout? This action cannot be undone.')) {
            // Clear layout data
            this.layoutData = {
                upper_deck: { rows: {} },
                lower_deck: { rows: {} }
            };

            // Clear visual elements
            this.upperDeckGrid.querySelectorAll('.seat-item').forEach(seat => seat.remove());
            this.lowerDeckGrid.querySelectorAll('.seat-item').forEach(seat => seat.remove());

            // Update counts
            this.updateSeatCounts();
            this.updateLayoutDataInput();
            this.hideSeatProperties();
        }
    }

    async showPreview() {
        try {
            const response = await fetch(this.previewUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    layout_data: JSON.stringify(this.layoutData)
                })
            });

            const result = await response.json();

            if (result.success) {
                this.previewContent.innerHTML = `
                    <div class="mb-3">
                        <h6>Generated HTML Layout:</h6>
                        <pre class="bg-light p-3" style="max-height: 300px; overflow-y: auto;">${result.html_layout}</pre>
                    </div>
                    <div>
                        <h6>Processed Layout Data:</h6>
                        <pre class="bg-light p-3" style="max-height: 300px; overflow-y: auto;">${JSON.stringify(result.processed_layout, null, 2)}</pre>
                    </div>
                `;

                const modal = new bootstrap.Modal(this.previewModal);
                modal.show();
            } else {
                alert('Error generating preview: ' + result.error);
            }
        } catch (error) {
            alert('Error generating preview: ' + error.message);
        }
    }

    getLayoutData() {
        return this.layoutData;
    }

    loadExistingData() {
        const existingData = this.layoutDataInput.value;
        if (existingData && existingData !== '{}') {
            try {
                this.layoutData = JSON.parse(existingData);
                this.renderExistingLayout();
            } catch (error) {
                console.error('Error loading existing layout data:', error);
            }
        }
    }

    renderExistingLayout() {
        // Clear existing seats
        this.upperDeckGrid.querySelectorAll('.seat-item').forEach(seat => seat.remove());
        this.lowerDeckGrid.querySelectorAll('.seat-item').forEach(seat => seat.remove());

        // Render upper deck seats
        Object.entries(this.layoutData.upper_deck.rows).forEach(([rowNum, seats]) => {
            seats.forEach(seat => {
                this.createSeatElement('upper_deck', seat);
            });
        });

        // Render lower deck seats
        Object.entries(this.layoutData.lower_deck.rows).forEach(([rowNum, seats]) => {
            seats.forEach(seat => {
                this.createSeatElement('lower_deck', seat);
            });
        });

        this.updateSeatCounts();
    }
}
