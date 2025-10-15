/**
 * Bus Seat Layout Editor - Proper Bus Structure with Dynamic Grid
 * Creates bus layout with busSeatlft + busSeatrgt structure
 * Rows are horizontal, columns are vertical
 * Aisle is void space where no seats can be placed
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
        this.seatLayoutSelect = options.seatLayoutSelect;
        this.columnsPerRowInput = options.columnsPerRowInput;
        this.upperDeckSection = options.upperDeckSection;
        this.lowerDeckLabel = options.lowerDeckLabel;

        this.selectedSeat = null;
        this.seatCounter = 1;
        this.deckType = 'single';
        this.seatLayout = '2x1';
        this.columnsPerRow = 10; // Default number of columns per row

        // Grid configuration
        this.cellWidth = 40; // Bigger cells for better visibility
        this.cellHeight = 40; // Bigger cells for better visibility
        this.aisleHeight = 50; // Aisle gap height

        this.layoutData = {
            upper_deck: { seats: [] },
            lower_deck: { seats: [] }
        };

        this.init();
    }

    init() {
        console.log('Bus Seat Layout Editor initialized');
        this.setupEventListeners();
        this.setupDragAndDrop();
        this.createBusLayout();
        this.loadExistingData();
        console.log('Bus Seat Layout Editor setup complete');
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

        // Deck type change
        if (this.deckTypeSelect) {
            this.deckTypeSelect.addEventListener('change', (e) => {
                this.setDeckType(e.target.value);
            });
        }

        // Seat layout change
        if (this.seatLayoutSelect) {
            this.seatLayoutSelect.addEventListener('change', (e) => {
                this.setSeatLayout(e.target.value);
            });
        }

        // Columns per row change
        if (this.columnsPerRowInput) {
            this.columnsPerRowInput.addEventListener('change', (e) => {
                this.setColumnsPerRow(parseInt(e.target.value));
            });
        }

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
                    const data = e.dataTransfer.getData('text/plain');
                    const rect = grid.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;

                    const deck = grid.id === 'upperDeckGrid' ? 'upper_deck' : 'lower_deck';
                    console.log('Drop event:', deck, x, y, data);

                    if (data === 'reposition' && this.draggingSeat) {
                        // Handle seat repositioning
                        this.moveSeatToPosition(this.draggingSeat, deck, x, y);
                    } else {
                        // Handle new seat creation
                        const seatData = JSON.parse(data);
                        this.addSeatToPosition(deck, x, y, seatData.type, seatData.category);
                    }
                } catch (error) {
                    console.error('Error parsing drop data:', error);
                }
            });
        });

        console.log('Drag and drop setup complete');
    }

    createBusLayout() {
        // Create proper bus structure for both decks
        [this.upperDeckGrid, this.lowerDeckGrid].forEach(grid => {
            this.createDeckLayout(grid);
        });
    }

    createDeckLayout(grid) {
        // Clear existing content
        grid.innerHTML = '';

        // Parse seat layout to determine structure
        const [leftSeats, rightSeats] = this.seatLayout.split('x').map(Number);
        const aisleColumns = 1; // Aisle is always 1 column wide

        // Create bus structure
        const busStructure = document.createElement('div');
        busStructure.className = 'outerseat';
        busStructure.style.display = 'flex';
        busStructure.style.width = '100%';
        busStructure.style.height = '100%';

        // Create busSeatlft (driver/cabin area)
        const busSeatlft = document.createElement('div');
        busSeatlft.className = 'busSeatlft';
        busSeatlft.style.width = '80px';
        busSeatlft.style.height = '100%';
        busSeatlft.style.backgroundColor = '#f0f0f0';
        busSeatlft.style.border = '1px solid #ccc';
        busSeatlft.style.display = 'flex';
        busSeatlft.style.alignItems = 'center';
        busSeatlft.style.justifyContent = 'center';
        busSeatlft.style.fontSize = '12px';
        busSeatlft.style.color = '#666';
        busSeatlft.textContent = 'DRIVER';

        // Create busSeatrgt (seat area)
        const busSeatrgt = document.createElement('div');
        busSeatrgt.className = 'busSeatrgt';
        busSeatrgt.style.width = (this.columnsPerRow * this.cellWidth) + 'px';
        busSeatrgt.style.height = '100%';
        busSeatrgt.style.position = 'relative';

        // Create busSeat container
        const busSeat = document.createElement('div');
        busSeat.className = 'busSeat';
        busSeat.style.width = '100%';
        busSeat.style.height = '100%';
        busSeat.style.position = 'relative';

        // Create seatcontainer
        const seatcontainer = document.createElement('div');
        seatcontainer.className = 'seatcontainer clearfix';
        seatcontainer.style.width = (this.columnsPerRow * this.cellWidth) + 'px';
        seatcontainer.style.height = '100%';
        seatcontainer.style.position = 'relative';

        // Generate seat positions based on layout
        this.generateSeatPositions(seatcontainer, leftSeats, rightSeats, aisleColumns);

        // Assemble structure
        busSeat.appendChild(seatcontainer);
        busSeatrgt.appendChild(busSeat);
        busStructure.appendChild(busSeatlft);
        busStructure.appendChild(busSeatrgt);

        grid.appendChild(busStructure);
    }

    generateSeatPositions(container, leftSeats, rightSeats, aisleColumns) {
        // Calculate total rows: leftSeats rows above + rightSeats rows below
        const totalRows = leftSeats + rightSeats;
        let currentTop = 0;
        let rowIndex = 0;

        // Create rows above the aisle
        for (let row = 0; row < leftSeats; row++) {
            this.createSeatRow(container, currentTop, rowIndex, 'above');
            currentTop += this.cellHeight;
            rowIndex++;
        }

        // Create aisle (void space)
        const aisleDiv = document.createElement('div');
        aisleDiv.className = 'aisle-row';
        aisleDiv.style.position = 'absolute';
        aisleDiv.style.top = currentTop + 'px';
        aisleDiv.style.left = '0px';
        aisleDiv.style.width = (this.columnsPerRow * this.cellWidth) + 'px';
        aisleDiv.style.height = this.aisleHeight + 'px';
        aisleDiv.style.backgroundColor = '#e7f3ff';
        aisleDiv.style.border = '2px solid #007bff';
        aisleDiv.style.display = 'flex';
        aisleDiv.style.alignItems = 'center';
        aisleDiv.style.justifyContent = 'center';
        aisleDiv.style.fontSize = '14px';
        aisleDiv.style.fontWeight = 'bold';
        aisleDiv.style.color = '#007bff';
        aisleDiv.textContent = 'AISLE';
        aisleDiv.style.zIndex = '10';
        container.appendChild(aisleDiv);

        currentTop += this.aisleHeight;

        // Create rows below the aisle
        for (let row = 0; row < rightSeats; row++) {
            this.createSeatRow(container, currentTop, rowIndex, 'below');
            currentTop += this.cellHeight;
            rowIndex++;
        }
    }

    createSeatRow(container, top, rowIndex, position) {
        // Create seat positions based on columns per row
        for (let col = 0; col < this.columnsPerRow; col++) {
            const left = col * this.cellWidth;
            this.createSeatPosition(container, left, top, rowIndex, col, position);
        }
    }

    createSeatPosition(container, left, top, row, col, side) {
        const seatPos = document.createElement('div');
        seatPos.className = 'seat-position';
        seatPos.dataset.row = row;
        seatPos.dataset.col = col;
        seatPos.dataset.side = side;
        seatPos.style.position = 'absolute';
        seatPos.style.left = left + 'px';
        seatPos.style.top = top + 'px';
        seatPos.style.width = this.cellWidth + 'px';
        seatPos.style.height = this.cellHeight + 'px';
        seatPos.style.border = '1px dashed #ccc';
        seatPos.style.backgroundColor = 'rgba(0, 123, 255, 0.1)';
        seatPos.style.display = 'flex';
        seatPos.style.alignItems = 'center';
        seatPos.style.justifyContent = 'center';
        seatPos.style.fontSize = '10px';
        seatPos.style.color = '#666';
        seatPos.style.cursor = 'pointer';
        seatPos.style.transition = 'background-color 0.2s';

        // Add drop zone indicator
        seatPos.innerHTML = '<span>+</span>';

        // Add hover effect
        seatPos.addEventListener('mouseenter', () => {
            seatPos.style.backgroundColor = 'rgba(0, 123, 255, 0.2)';
        });

        seatPos.addEventListener('mouseleave', () => {
            seatPos.style.backgroundColor = 'rgba(0, 123, 255, 0.1)';
        });

        // Add click handler for seat editing
        seatPos.addEventListener('click', (e) => {
            if (seatPos.querySelector('.seat-item')) {
                this.selectSeat(seatPos.querySelector('.seat-item'));
            }
        });

        container.appendChild(seatPos);
    }

    moveSeatToPosition(seatElement, deck, x, y) {
        // Find the target position
        const targetPosition = this.findPositionAt(deck, x, y);
        if (!targetPosition) {
            console.log('No valid position found for seat move');
            return;
        }

        // Check if target position is already occupied
        if (targetPosition.querySelector('.seat-item')) {
            console.log('Target position already occupied');
            return;
        }

        // Get seat data
        const seatData = JSON.parse(seatElement.dataset.seatData);
        const oldPosition = seatElement.parentElement;

        // Remove from old position
        oldPosition.innerHTML = '<span>+</span>';
        this.clearOccupiedCells(oldPosition.parentElement, seatData.row, seatData.col, seatData.type);

        // Update seat data with new position
        const newRow = parseInt(targetPosition.dataset.row);
        const newCol = parseInt(targetPosition.dataset.col);
        const newSide = targetPosition.dataset.side;

        seatData.row = newRow;
        seatData.col = newCol;
        seatData.side = newSide;
        seatData.position = newRow * 30; // Update position based on row
        seatData.left = newCol * 40; // Update left based on column

        // Update layout data
        const deckData = this.layoutData[deck];
        const seatIndex = deckData.seats.findIndex(seat => seat.seat_id === seatData.seat_id);
        if (seatIndex !== -1) {
            deckData.seats[seatIndex] = { ...seatData };
        }

        // Place in new position
        targetPosition.innerHTML = '';
        targetPosition.appendChild(seatElement);
        this.markOccupiedCells(targetPosition.parentElement, newRow, newCol, seatData.type);

        // Update seat element data
        seatElement.dataset.seatData = JSON.stringify(seatData);

        console.log('Seat moved successfully:', seatData.seat_id, 'to', newRow, newCol);
    }

    addSeatToPosition(deck, x, y, type, category) {
        // For single decker, only allow lower deck
        if (this.deckType === 'single' && deck === 'upper_deck') {
            console.log('Cannot add seats to upper deck in single decker bus');
            return;
        }

        // Find the seat position at this location
        const grid = deck === 'upper_deck' ? this.upperDeckGrid : this.lowerDeckGrid;
        const seatPosition = this.getSeatPositionAt(grid, x, y);

        if (!seatPosition) {
            console.log('No seat position found at location');
            return;
        }

        // Check if position already has a seat
        if (seatPosition.querySelector('.seat-item')) {
            console.log('Position already has a seat');
            return;
        }

        // Get position data
        const row = parseInt(seatPosition.dataset.row);
        const col = parseInt(seatPosition.dataset.col);
        const side = seatPosition.dataset.side;

        // Check if we can place the seat (considering seat dimensions)
        if (!this.canPlaceSeat(grid, row, col, type)) {
            console.log('Cannot place seat - not enough space');
            return;
        }

        // Generate seat ID (matching API format)
        const seatId = this.generateSeatId(deck, row, col, side);

        // Create seat data
        const position = parseInt(seatPosition.style.top) || (row * this.cellHeight);
        const left = parseInt(seatPosition.style.left) || (col * this.cellWidth);


        const seatData = {
            seat_id: seatId,
            type: type,
            category: category,
            price: 0,
            position: position,
            left: left,
            row: row,
            col: col,
            side: side,
            width: this.getSeatWidth(type),
            height: this.getSeatHeight(type),
            is_available: true,
            is_sleeper: category === 'sleeper'
        };

        // Add to layout data
        if (!this.layoutData[deck].seats) {
            this.layoutData[deck].seats = [];
        }
        this.layoutData[deck].seats.push(seatData);

        // Create visual seat element
        this.createSeatElement(deck, seatData, seatPosition);

        // Mark occupied cells
        this.markOccupiedCells(grid, row, col, type);

        // Update counts
        this.updateSeatCounts();
        this.updateLayoutDataInput();

        console.log('Seat added:', seatData);
    }

    getSeatPositionAt(grid, x, y) {
        const positions = grid.querySelectorAll('.seat-position');
        for (let pos of positions) {
            const rect = pos.getBoundingClientRect();
            const gridRect = grid.getBoundingClientRect();
            const posX = rect.left - gridRect.left;
            const posY = rect.top - gridRect.top;

            if (x >= posX && x <= posX + rect.width &&
                y >= posY && y <= posY + rect.height) {
                return pos;
            }
        }
        return null;
    }

    generateSeatId(deck, row, col, side) {
        // Generate seat ID matching API format
        const deckPrefix = deck === 'upper_deck' ? 'U' : 'L';
        const seatNumber = (row * 10) + (col + 1);
        return `${deckPrefix}${seatNumber}`;
    }

    getSeatWidth(type) {
        switch (type) {
            case 'nseat': return 1; // Seater: 1x1
            case 'hseat': return 2; // Horizontal sleeper: 2x1
            case 'vseat': return 1; // Vertical sleeper: 1x2
            default: return 1;
        }
    }

    getSeatHeight(type) {
        switch (type) {
            case 'nseat': return 1; // Seater: 1x1
            case 'hseat': return 1; // Horizontal sleeper: 2x1
            case 'vseat': return 2; // Vertical sleeper: 1x2
            default: return 1;
        }
    }

    canPlaceSeat(grid, row, col, type) {
        const width = this.getSeatWidth(type);
        const height = this.getSeatHeight(type);

        // Check if seat fits within grid bounds
        if (col + width > this.columnsPerRow || row + height > this.getTotalRows()) {
            return false;
        }

        // Check if all required cells are empty
        for (let r = row; r < row + height; r++) {
            for (let c = col; c < col + width; c++) {
                const cell = grid.querySelector(`[data-row="${r}"][data-col="${c}"]`);
                if (!cell || cell.querySelector('.seat-item')) {
                    return false;
                }
            }
        }

        return true;
    }

    markOccupiedCells(grid, row, col, type) {
        const width = this.getSeatWidth(type);
        const height = this.getSeatHeight(type);

        // Mark all cells as occupied
        for (let r = row; r < row + height; r++) {
            for (let c = col; c < col + width; c++) {
                const cell = grid.querySelector(`[data-row="${r}"][data-col="${c}"]`);
                if (cell && !(r === row && c === col)) { // Skip the main cell
                    cell.style.backgroundColor = '#f0f0f0';
                    cell.style.pointerEvents = 'none';
                }
            }
        }
    }

    getTotalRows() {
        const [leftSeats, rightSeats] = this.seatLayout.split('x').map(Number);
        return leftSeats + rightSeats;
    }

    createSeatElement(deck, seatData, position) {
        const seatElement = document.createElement('div');
        seatElement.className = `seat-item ${seatData.type}`;
        seatElement.style.position = 'absolute';
        seatElement.style.left = '0px';
        seatElement.style.top = '0px';
        seatElement.style.width = (seatData.width * this.cellWidth) + 'px';
        seatElement.style.height = (seatData.height * this.cellHeight) + 'px';
        seatElement.style.border = '2px solid #333';
        seatElement.style.borderRadius = '4px';
        seatElement.style.display = 'flex';
        seatElement.style.alignItems = 'center';
        seatElement.style.justifyContent = 'center';
        seatElement.style.fontSize = '12px';
        seatElement.style.fontWeight = 'bold';
        seatElement.style.cursor = 'pointer';
        seatElement.style.zIndex = '5';
        seatElement.style.transition = 'all 0.2s ease';
        seatElement.textContent = seatData.seat_id;
        seatElement.dataset.seatId = seatData.seat_id;
        seatElement.dataset.deck = deck;
        seatElement.dataset.seatData = JSON.stringify(seatData);

        // Set seat colors based on type
        switch (seatData.type) {
            case 'nseat':
                seatElement.style.backgroundColor = '#fff';
                seatElement.style.color = '#333';
                seatElement.style.borderColor = '#666';
                break;
            case 'hseat':
                seatElement.style.backgroundColor = '#e3f2fd';
                seatElement.style.color = '#1976d2';
                seatElement.style.borderColor = '#1976d2';
                break;
            case 'vseat':
                seatElement.style.backgroundColor = '#f3e5f5';
                seatElement.style.color = '#7b1fa2';
                seatElement.style.borderColor = '#7b1fa2';
                break;
        }

        // Add hover effect
        seatElement.addEventListener('mouseenter', () => {
            seatElement.style.transform = 'scale(1.05)';
            seatElement.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.2)';
        });

        seatElement.addEventListener('mouseleave', () => {
            seatElement.style.transform = 'scale(1)';
            seatElement.style.boxShadow = 'none';
        });

        // Handle seat click for editing
        seatElement.addEventListener('click', (e) => {
            e.stopPropagation();
            this.selectSeat(seatElement);
        });

        // Make seat draggable for repositioning
        seatElement.draggable = true;
        seatElement.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('text/plain', 'reposition');
            e.dataTransfer.effectAllowed = 'move';
            this.draggingSeat = seatElement;
            seatElement.style.opacity = '0.5';
        });

        seatElement.addEventListener('dragend', (e) => {
            seatElement.style.opacity = '1';
            this.draggingSeat = null;
        });

        // Clear position content and add seat
        position.innerHTML = '';
        position.appendChild(seatElement);
    }

    selectSeat(seatElement) {
        // Remove previous selection
        document.querySelectorAll('.seat-item.selected').forEach(seat => {
            seat.classList.remove('selected');
        });

        // Select current seat
        seatElement.classList.add('selected');
        this.selectedSeat = seatElement;

        // Show seat properties panel
        const seatData = JSON.parse(seatElement.dataset.seatData);
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
        document.querySelectorAll('.seat-item.selected').forEach(seat => {
            seat.classList.remove('selected');
        });
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
        const seatData = JSON.parse(this.selectedSeat.dataset.seatData);

        this.layoutData[deck].seats.forEach(seat => {
            if (seat.seat_id === seatData.seat_id) {
                seat.seat_id = seatId;
                seat.price = price;
                seat.type = type;
            }
        });

        // Update seat data in element
        seatData.seat_id = seatId;
        seatData.price = price;
        seatData.type = type;
        this.selectedSeat.dataset.seatData = JSON.stringify(seatData);

        // Update counts
        this.updateSeatCounts();
        this.updateLayoutDataInput();

        console.log('Seat updated:', { seatId, price, type });
    }

    deleteSelectedSeat() {
        if (!this.selectedSeat) return;

        if (confirm('Delete this seat?')) {
            const seatId = this.selectedSeat.dataset.seatId;
            const deck = this.selectedSeat.dataset.deck;
            const seatData = JSON.parse(this.selectedSeat.dataset.seatData);

            // Remove from layout data
            this.layoutData[deck].seats = this.layoutData[deck].seats.filter(
                seat => seat.seat_id !== seatId
            );

            // Clear occupied cells
            const grid = deck === 'upper_deck' ? this.upperDeckGrid : this.lowerDeckGrid;
            this.clearOccupiedCells(grid, seatData.row, seatData.col, seatData.type);

            // Remove visual element and restore position
            const position = this.selectedSeat.parentElement;
            position.innerHTML = '<span>+</span>';

            // Hide properties panel
            this.hideSeatProperties();

            // Update counts
            this.updateSeatCounts();
            this.updateLayoutDataInput();

            console.log('Seat deleted:', seatId);
        }
    }

    clearOccupiedCells(grid, row, col, type) {
        const width = this.getSeatWidth(type);
        const height = this.getSeatHeight(type);

        // Clear all occupied cells
        for (let r = row; r < row + height; r++) {
            for (let c = col; c < col + width; c++) {
                const cell = grid.querySelector(`[data-row="${r}"][data-col="${c}"]`);
                if (cell) {
                    cell.style.backgroundColor = 'rgba(0, 123, 255, 0.1)';
                    cell.style.pointerEvents = 'auto';
                    if (!cell.querySelector('.seat-item')) {
                        cell.innerHTML = '<span>+</span>';
                    }
                }
            }
        }
    }

    setDeckType(deckType) {
        this.deckType = deckType;

        // Clear upper deck data for single decker
        if (deckType === 'single') {
            this.layoutData.upper_deck = { seats: [] };
            // Clear upper deck visual elements
            this.upperDeckGrid.innerHTML = '';
        }

        this.updateSeatCounts();
        this.updateLayoutDataInput();
    }

    setSeatLayout(layout) {
        this.seatLayout = layout;

        // Recreate bus layout
        this.createBusLayout();

        // Clear existing seats
        this.clearAllSeats();

        console.log('Seat layout changed to:', layout);
    }

    setColumnsPerRow(columns) {
        this.columnsPerRow = columns;

        // Recreate bus layout
        this.createBusLayout();

        // Clear existing seats
        this.clearAllSeats();

        console.log('Columns per row changed to:', columns);
    }

    clearAllSeats() {
        // Clear layout data
        this.layoutData = {
            upper_deck: { seats: [] },
            lower_deck: { seats: [] }
        };

        // Clear visual elements but keep positions
        this.upperDeckGrid.querySelectorAll('.seat-item').forEach(seat => {
            const position = seat.parentElement;
            position.innerHTML = '<span>+</span>';
        });
        this.lowerDeckGrid.querySelectorAll('.seat-item').forEach(seat => {
            const position = seat.parentElement;
            position.innerHTML = '<span>+</span>';
        });

        // Update counts
        this.updateSeatCounts();
        this.updateLayoutDataInput();
        this.hideSeatProperties();
    }

    updateSeatCounts() {
        let upperCount = 0;
        let lowerCount = 0;

        // Count upper deck seats (only for double decker)
        if (this.deckType === 'double') {
            upperCount = this.layoutData.upper_deck.seats ? this.layoutData.upper_deck.seats.length : 0;
        }

        // Count lower deck seats
        lowerCount = this.layoutData.lower_deck.seats ? this.layoutData.lower_deck.seats.length : 0;

        this.upperDeckSeatsInput.value = upperCount;
        this.lowerDeckSeatsInput.value = lowerCount;
        this.totalSeatsInput.value = upperCount + lowerCount;
    }

    updateLayoutDataInput() {
        this.layoutDataInput.value = JSON.stringify(this.layoutData);
    }

    clearLayout() {
        if (confirm('Clear the entire layout? This action cannot be undone.')) {
            this.clearAllSeats();
        }
    }

    async showPreview() {
        try {
            // Get CSRF token from meta tag or form
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                document.querySelector('input[name="_token"]')?.value ||
                '';

            console.log('Sending preview request to:', this.previewUrl);
            console.log('Layout data:', this.layoutData);
            console.log('Upper deck seats:', this.layoutData.upper_deck.seats);
            console.log('Lower deck seats:', this.layoutData.lower_deck.seats);

            const response = await fetch(this.previewUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    layout_data: JSON.stringify(this.layoutData)
                })
            });

            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);

            // Check if response is ok
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Server error response:', errorText);
                throw new Error(`Server error: ${response.status} - ${response.statusText}`);
            }

            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const responseText = await response.text();
                console.error('Non-JSON response:', responseText);
                throw new Error('Server returned non-JSON response. Check server logs.');
            }

            const result = await response.json();
            console.log('Preview result:', result);

            if (result.success) {
                this.previewContent.innerHTML = `
                    <style>
                        .preview-seat-item {
                            position: absolute;
                            border: 2px solid;
                            border-radius: 4px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 10px;
                            font-weight: bold;
                            cursor: pointer;
                        }
                        .preview-seat-item.nseat {
                            width: 30px;
                            height: 25px;
                            background-color: #fff;
                            border-color: #666;
                            color: #333;
                        }
                        .preview-seat-item.hseat {
                            width: 40px;
                            height: 25px;
                            background-color: #e3f2fd;
                            border-color: #1976d2;
                            color: #1976d2;
                        }
                        .preview-seat-item.vseat {
                            width: 30px;
                            height: 40px;
                            background-color: #f3e5f5;
                            border-color: #7b1fa2;
                            color: #7b1fa2;
                        }
                        .preview-bus-layout {
                            background-color: #f8f9fa;
                            border: 2px solid #ddd;
                            padding: 20px;
                        }
                        .preview-bus-layout .outerseat,
                        .preview-bus-layout .outerlowerseat {
                            display: flex;
                            margin-bottom: 20px;
                            background-color: white;
                            border: 1px solid #ccc;
                            border-radius: 8px;
                            overflow: hidden;
                            min-height: fit-content;
                            height: auto;
                        }
                        .preview-bus-layout .outerlowerseat {
                            margin-bottom: 0;
                        }
                        .preview-bus-layout .busSeatlft {
                            width: 60px;
                            background-color: #6c757d;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-weight: bold;
                            font-size: 12px;
                            flex-shrink: 0;
                            min-height: 120px;
                        }
                        .preview-bus-layout .busSeatrgt {
                            flex: 1;
                            position: relative;
                            padding: 10px;
                            min-height: fit-content;
                            height: auto;
                        }
                        .preview-bus-layout .seatcontainer {
                            position: relative;
                            min-height: fit-content;
                            height: auto;
                            width: 100%;
                        }
                    </style>
                    <div class="mb-3">
                        <h6>Generated HTML Layout:</h6>
                        <div class="border p-3 bg-white" style="max-height: 300px; overflow-y: auto;">
                            <div class="preview-bus-layout">
                                ${result.html_layout.replace(/class="nseat"/g, 'class="preview-seat-item nseat"').replace(/class="hseat"/g, 'class="preview-seat-item hseat"').replace(/class="vseat"/g, 'class="preview-seat-item vseat"')}
                            </div>
                        </div>
                    </div>
                    <div>
                        <h6>Processed Layout Data:</h6>
                        <div class="border p-3 bg-white" style="max-height: 300px; overflow-y: auto;">
                            <pre class="text-dark mb-0" style="white-space: pre-wrap; font-size: 12px;">${JSON.stringify(result.processed_layout, null, 2)}</pre>
                        </div>
                    </div>
                `;

                const modal = new bootstrap.Modal(this.previewModal);
                modal.show();
            } else {
                alert('Error generating preview: ' + (result.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Preview error:', error);
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
        // Clear existing seats but keep positions
        this.upperDeckGrid.querySelectorAll('.seat-item').forEach(seat => {
            const position = seat.parentElement;
            position.innerHTML = '<span>+</span>';
        });
        this.lowerDeckGrid.querySelectorAll('.seat-item').forEach(seat => {
            const position = seat.parentElement;
            position.innerHTML = '<span>+</span>';
        });

        // Render upper deck seats
        if (this.layoutData.upper_deck.seats) {
            this.layoutData.upper_deck.seats.forEach(seat => {
                const grid = this.upperDeckGrid;
                const position = grid.querySelector(`[data-row="${seat.row}"][data-col="${seat.col}"][data-side="${seat.side}"]`);
                if (position) {
                    this.createSeatElement('upper_deck', seat, position);
                }
            });
        }

        // Render lower deck seats
        if (this.layoutData.lower_deck.seats) {
            this.layoutData.lower_deck.seats.forEach(seat => {
                const grid = this.lowerDeckGrid;
                const position = grid.querySelector(`[data-row="${seat.row}"][data-col="${seat.col}"][data-side="${seat.side}"]`);
                if (position) {
                    this.createSeatElement('lower_deck', seat, position);
                }
            });
        }

        this.updateSeatCounts();
    }
}