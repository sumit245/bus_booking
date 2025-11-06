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
    this.deckType = "single";
    this.seatLayout = "2x1";
    this.columnsPerRow = 10; // Default number of columns per row

    // Grid configuration
    this.cellWidth = 50; // Bigger cells for better visibility
    this.cellHeight = 50; // Bigger cells for better visibility
    this.aisleHeight = 60; // Aisle gap height

    this.layoutData = {
      upper_deck: { seats: [] },
      lower_deck: { seats: [] },
    };

    this.init();
  }

  init() {
    console.log("Bus Seat Layout Editor initialized");
    this.setupEventListeners();
    this.setupDragAndDrop();

    // First, load existing configuration if it exists
    // This will infer seat layout from existing seats if configuration is missing
    this.loadExistingConfiguration();

    console.log("After loadExistingConfiguration:", {
      seatLayout: this.seatLayout,
      deckType: this.deckType,
      columnsPerRow: this.columnsPerRow
    });

    // Create the bus layout with the loaded/inferred configuration
    // This must happen after loadExistingConfiguration so we have the correct seatLayout
    this.createBusLayout();

    // Apply deck type settings after layout is created
    this.applyDeckTypeSettings();

    // Finally, load and render existing seat data
    // This will also check if we need to recreate the layout with more rows
    this.loadExistingData();

    console.log("Bus Seat Layout Editor setup complete");

    // Debug: Check if grids are properly initialized
    console.log("Upper deck grid:", this.upperDeckGrid);
    console.log("Lower deck grid:", this.lowerDeckGrid);
    console.log(
      "Upper deck grid children:",
      this.upperDeckGrid?.children.length,
    );
    console.log(
      "Lower deck grid children:",
      this.lowerDeckGrid?.children.length,
    );
    console.log("Final seat layout:", this.seatLayout);
    console.log("Final deck type:", this.deckType);
    console.log("Final columns per row:", this.columnsPerRow);
  }

  setupEventListeners() {
    // Seat type selection
    document.querySelectorAll(".seat-type-item").forEach((item) => {
      item.addEventListener("click", (e) => {
        document
          .querySelectorAll(".seat-type-item")
          .forEach((i) => i.classList.remove("selected"));
        item.classList.add("selected");
      });
    });

    // Update seat button
    this.updateSeatBtn.addEventListener("click", () =>
      this.updateSelectedSeat(),
    );

    // Delete seat button
    this.deleteSeatBtn.addEventListener("click", () =>
      this.deleteSelectedSeat(),
    );

    // Preview button
    this.previewBtn.addEventListener("click", () => this.showPreview());

    // Clear button
    this.clearBtn.addEventListener("click", () => this.clearLayout());

    // Deck type change
    if (this.deckTypeSelect) {
      this.deckTypeSelect.addEventListener("change", (e) => {
        this.setDeckType(e.target.value);
      });
    }

    // Seat layout change
    if (this.seatLayoutSelect) {
      this.seatLayoutSelect.addEventListener("change", (e) => {
        this.setSeatLayout(e.target.value);
      });
    }

    // Columns per row change
    if (this.columnsPerRowInput) {
      this.columnsPerRowInput.addEventListener("change", (e) => {
        this.setColumnsPerRow(parseInt(e.target.value));
      });
    }

    // Close properties panel when clicking outside
    document.addEventListener("click", (e) => {
      if (
        !this.seatPropertiesPanel.contains(e.target) &&
        !e.target.closest(".seat-item")
      ) {
        this.hideSeatProperties();
      }
    });
  }

  setupDragAndDrop() {
    console.log("Setting up drag and drop...");

    // Make seat type items draggable
    const seatTypeItems = document.querySelectorAll(".seat-type-item");
    console.log("Found seat type items:", seatTypeItems.length);

    seatTypeItems.forEach((item, index) => {
      item.draggable = true;
      console.log(`Making item ${index} draggable:`, item.dataset.type);

      item.addEventListener("dragstart", (e) => {
        const seatType = item.dataset.type;
        const category = item.dataset.category;
        e.dataTransfer.setData(
          "text/plain",
          JSON.stringify({
            type: seatType,
            category: category,
          }),
        );
        item.classList.add("dragging");
        console.log("Drag started:", seatType, category);
      });

      item.addEventListener("dragend", (e) => {
        item.classList.remove("dragging");
        console.log("Drag ended");
      });
    });

    // Setup drop zones for both decks
    [this.upperDeckGrid, this.lowerDeckGrid].forEach((grid) => {
      console.log(
        "Setting up drop zone for:",
        grid?.id,
        "Grid exists:",
        !!grid,
      );

      if (!grid) {
        console.error("Grid is null or undefined:", grid);
        return;
      }

      grid.addEventListener("dragover", (e) => {
        e.preventDefault();
        e.stopPropagation();
        grid.classList.add("drag-over");
        console.log("Drag over on:", grid.id);
      });

      grid.addEventListener("dragleave", (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (!grid.contains(e.relatedTarget)) {
          grid.classList.remove("drag-over");
        }
      });

      grid.addEventListener("drop", (e) => {
        e.preventDefault();
        e.stopPropagation();
        grid.classList.remove("drag-over");

        try {
          const data = e.dataTransfer.getData("text/plain");
          const rect = grid.getBoundingClientRect();
          const x = e.clientX - rect.left;
          const y = e.clientY - rect.top;

          const deck =
            grid.id === "upperDeckGrid" ? "upper_deck" : "lower_deck";
          console.log(
            "Drop event on:",
            grid.id,
            "Deck:",
            deck,
            "Position:",
            x,
            y,
            "Data:",
            data,
          );

          if (data === "reposition" && this.draggingSeat) {
            // Handle seat repositioning
            this.moveSeatToPosition(this.draggingSeat, deck, x, y);
          } else {
            // Handle new seat creation
            const seatData = JSON.parse(data);
            this.addSeatToPosition(
              deck,
              x,
              y,
              seatData.type,
              seatData.category,
            );
          }
        } catch (error) {
          console.error("Error parsing drop data:", error);
        }
      });
    });

    console.log("Drag and drop setup complete");
  }

  createBusLayout() {
    // Create proper bus structure for both decks
    [this.upperDeckGrid, this.lowerDeckGrid].forEach((grid) => {
      this.createDeckLayout(grid);
    });
  }

  createDeckLayout(grid) {
    console.log("=== CREATING DECK LAYOUT ===");
    console.log(
      "createDeckLayout called for grid:",
      grid?.id,
      "Grid exists:",
      !!grid,
    );

    if (!grid) {
      console.error("Grid is null or undefined in createDeckLayout");
      return;
    }

    console.log("Grid children before clear:", grid.children.length);
    // Clear existing content
    grid.innerHTML = "";
    console.log("Grid children after clear:", grid.children.length);

    // Parse seat layout to determine structure
    const [leftSeats, rightSeats] = this.seatLayout.split("x").map(Number);
    const aisleColumns = 1; // Aisle is always 1 column wide

    console.log("Creating deck layout with:", {
      leftSeats,
      rightSeats,
      aisleColumns,
      columnsPerRow: this.columnsPerRow,
    });

    // Determine the correct class based on deck type
    const isUpperDeck = grid.id === "upperDeckGrid";
    const deckClass = isUpperDeck ? "outerseat" : "outerlowerseat";
    const driverClass = isUpperDeck ? "upper" : "lower";

    console.log(
      "Creating deck with class:",
      deckClass,
      "Driver class:",
      driverClass,
    );
    console.log("Is upper deck:", isUpperDeck);

    // Create bus structure with correct class
    const busStructure = document.createElement("div");
    busStructure.className = deckClass;
    busStructure.style.display = "flex";
    busStructure.style.width = "100%";
    busStructure.style.height = "auto";
    busStructure.style.minHeight = "250px";

    // Create busSeatlft (driver/cabin area)
    const busSeatlft = document.createElement("div");
    busSeatlft.className = "busSeatlft";
    busSeatlft.style.width = "80px";
    busSeatlft.style.height = "auto";
    busSeatlft.style.minHeight = "250px";
    busSeatlft.style.backgroundColor = "#f0f0f0";
    busSeatlft.style.border = "1px solid #ccc";
    busSeatlft.style.display = "flex";
    busSeatlft.style.alignItems = "center";
    busSeatlft.style.justifyContent = "center";
    busSeatlft.style.fontSize = "12px";
    busSeatlft.style.color = "#666";
    busSeatlft.textContent = "DRIVER";

    // Create the inner div with correct class (upper/lower)
    const driverInner = document.createElement("div");
    driverInner.className = driverClass;
    busSeatlft.appendChild(driverInner);

    // Create busSeatrgt (seat area)
    const busSeatrgt = document.createElement("div");
    busSeatrgt.className = "busSeatrgt";
    busSeatrgt.style.width = this.columnsPerRow * this.cellWidth + "px";
    busSeatrgt.style.height = "auto";
    busSeatrgt.style.minHeight = "250px";
    busSeatrgt.style.position = "relative";

    // Create busSeat container
    const busSeat = document.createElement("div");
    busSeat.className = "busSeat";
    busSeat.style.width = "100%";
    busSeat.style.height = "auto";
    busSeat.style.minHeight = "250px";
    busSeat.style.position = "relative";

    // Create seatcontainer
    const seatcontainer = document.createElement("div");
    seatcontainer.className = "seatcontainer clearfix";
    seatcontainer.style.width = this.columnsPerRow * this.cellWidth + "px";
    seatcontainer.style.position = "relative";
    // Calculate height dynamically based on rows and aisle
    const totalRows = leftSeats + rightSeats;
    const calculatedHeight = (totalRows * this.cellHeight) + this.aisleHeight + 20; // +20 for padding
    seatcontainer.style.minHeight = calculatedHeight + "px";
    seatcontainer.style.height = "auto";

    // Generate seat positions based on layout
    this.generateSeatPositions(
      seatcontainer,
      leftSeats,
      rightSeats,
      aisleColumns,
    );

    // Sync busSeatlft height with seatcontainer height after positions are generated
    // Wait for next frame to ensure positions are rendered
    setTimeout(() => {
      const seatcontainerHeight = seatcontainer.offsetHeight;
      if (seatcontainerHeight > 250) {
        busSeatlft.style.minHeight = seatcontainerHeight + "px";
        busSeatlft.style.height = seatcontainerHeight + "px";
      }
    }, 0);

    // Create clr div for proper structure
    const clrDiv = document.createElement("div");
    clrDiv.className = "clr";

    // Assemble structure
    busSeat.appendChild(seatcontainer);
    busSeatrgt.appendChild(busSeat);
    busStructure.appendChild(busSeatlft);
    busStructure.appendChild(busSeatrgt);
    busStructure.appendChild(clrDiv);

    grid.appendChild(busStructure);

    console.log(
      "Deck layout created for",
      grid.id,
      "with class:",
      deckClass,
      "Children count:",
      grid.children.length,
    );
    console.log(
      "Seat positions created:",
      grid.querySelectorAll(".seat-position").length,
    );
    console.log("All seat positions in grid:");
    const allPositions = grid.querySelectorAll(".seat-position");
    allPositions.forEach((pos, i) => {
      console.log(`Position ${i}:`, {
        row: pos.dataset.row,
        col: pos.dataset.col,
        side: pos.dataset.side,
      });
    });
    console.log("=== DECK LAYOUT CREATION COMPLETE ===");
  }

  generateSeatPositions(container, leftSeats, rightSeats, aisleColumns) {
    console.log("generateSeatPositions called with:", {
      leftSeats,
      rightSeats,
      aisleColumns,
    });

    // Calculate total rows: leftSeats rows above + rightSeats rows below
    const totalRows = leftSeats + rightSeats;
    let currentTop = 0;
    let rowIndex = 0;

    console.log("Total rows to create:", totalRows);

    // Create rows above the aisle
    for (let row = 0; row < leftSeats; row++) {
      this.createSeatRow(container, currentTop, rowIndex, "above");
      currentTop += this.cellHeight;
      rowIndex++;
    }

    // Create aisle (void space)
    const aisleDiv = document.createElement("div");
    aisleDiv.className = "aisle-row";
    aisleDiv.style.position = "absolute";
    aisleDiv.style.top = currentTop + "px";
    aisleDiv.style.left = "0px";
    aisleDiv.style.width = this.columnsPerRow * this.cellWidth + "px";
    aisleDiv.style.height = this.aisleHeight + "px";
    aisleDiv.style.backgroundColor = "#e7f3ff";
    aisleDiv.style.border = "2px solid #007bff";
    aisleDiv.style.display = "flex";
    aisleDiv.style.alignItems = "center";
    aisleDiv.style.justifyContent = "center";
    aisleDiv.style.fontSize = "14px";
    aisleDiv.style.fontWeight = "bold";
    aisleDiv.style.color = "#007bff";
    aisleDiv.textContent = "AISLE";
    aisleDiv.style.zIndex = "10";
    container.appendChild(aisleDiv);

    currentTop += this.aisleHeight;

    // Create rows below the aisle
    for (let row = 0; row < rightSeats; row++) {
      this.createSeatRow(container, currentTop, rowIndex, "below");
      currentTop += this.cellHeight;
      rowIndex++;
    }
  }

  createSeatRow(container, top, rowIndex, position) {
    console.log("createSeatRow called:", {
      top,
      rowIndex,
      position,
      columnsPerRow: this.columnsPerRow,
    });

    // Create seat positions based on columns per row
    for (let col = 0; col < this.columnsPerRow; col++) {
      const left = col * this.cellWidth;
      this.createSeatPosition(container, left, top, rowIndex, col, position);
    }

    console.log("Created seat row with", this.columnsPerRow, "positions");
  }

  createSeatPosition(container, left, top, row, col, side) {
    console.log("createSeatPosition called:", { left, top, row, col, side });

    const seatPos = document.createElement("div");
    seatPos.className = "seat-position";
    seatPos.dataset.row = row;
    seatPos.dataset.col = col;
    seatPos.dataset.side = side;
    seatPos.style.position = "absolute";
    seatPos.style.left = left + "px";
    seatPos.style.top = top + "px";
    seatPos.style.width = this.cellWidth + "px";
    seatPos.style.height = this.cellHeight + "px";
    seatPos.style.border = "1px dashed #ccc";
    seatPos.style.backgroundColor = "rgba(0, 123, 255, 0.1)";
    seatPos.style.display = "flex";
    seatPos.style.alignItems = "center";
    seatPos.style.justifyContent = "center";
    seatPos.style.fontSize = "10px";
    seatPos.style.color = "#666";
    seatPos.style.cursor = "pointer";
    seatPos.style.transition = "background-color 0.2s";

    // Add drop zone indicator
    seatPos.innerHTML = "<span>+</span>";

    // Add hover effect
    seatPos.addEventListener("mouseenter", () => {
      seatPos.style.backgroundColor = "rgba(0, 123, 255, 0.2)";
    });

    seatPos.addEventListener("mouseleave", () => {
      seatPos.style.backgroundColor = "rgba(0, 123, 255, 0.1)";
    });

    // Add click handler for seat editing
    seatPos.addEventListener("click", (e) => {
      if (seatPos.querySelector(".seat-item")) {
        this.selectSeat(seatPos.querySelector(".seat-item"));
      }
    });

    container.appendChild(seatPos);
    console.log(
      "Seat position appended to container. Total positions:",
      container.children.length,
    );
  }

  moveSeatToPosition(seatElement, deck, x, y) {
    // Find the target position
    const targetPosition = this.findPositionAt(deck, x, y);
    if (!targetPosition) {
      console.log("No valid position found for seat move");
      return;
    }

    // Check if target position is already occupied
    if (targetPosition.querySelector(".seat-item")) {
      console.log("Target position already occupied");
      return;
    }

    // Get seat data
    const seatData = JSON.parse(seatElement.dataset.seatData);
    const oldPosition = seatElement.parentElement;

    // Remove from old position
    oldPosition.innerHTML = "<span>+</span>";
    this.clearOccupiedCells(
      oldPosition.parentElement,
      seatData.row,
      seatData.col,
      seatData.type,
    );

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
    const seatIndex = deckData.seats.findIndex(
      (seat) => seat.seat_id === seatData.seat_id,
    );
    if (seatIndex !== -1) {
      deckData.seats[seatIndex] = { ...seatData };
    }

    // Place in new position
    targetPosition.innerHTML = "";
    targetPosition.appendChild(seatElement);
    this.markOccupiedCells(
      targetPosition.parentElement,
      newRow,
      newCol,
      seatData.type,
    );

    // Update seat element data
    seatElement.dataset.seatData = JSON.stringify(seatData);

    console.log(
      "Seat moved successfully:",
      seatData.seat_id,
      "to",
      newRow,
      newCol,
    );
  }

  addSeatToPosition(deck, x, y, type, category) {
    console.log("addSeatToPosition called:", {
      deck,
      x,
      y,
      type,
      category,
      deckType: this.deckType,
    });

    // For single decker, only allow lower deck
    if (this.deckType === "single" && deck === "upper_deck") {
      console.log("Cannot add seats to upper deck in single decker bus");
      return;
    }

    // Find the seat position at this location
    const grid =
      deck === "upper_deck" ? this.upperDeckGrid : this.lowerDeckGrid;
    console.log("Using grid:", grid?.id, "Grid exists:", !!grid);

    const seatPosition = this.getSeatPositionAt(grid, x, y);
    console.log("Found seat position:", !!seatPosition, seatPosition);

    if (!seatPosition) {
      console.log("No seat position found at location");
      return;
    }

    // Check if position already has a seat
    if (seatPosition.querySelector(".seat-item")) {
      console.log("Position already has a seat");
      return;
    }

    // Get position data
    const row = parseInt(seatPosition.dataset.row);
    const col = parseInt(seatPosition.dataset.col);
    const side = seatPosition.dataset.side;

    // Check if we can place the seat (considering seat dimensions)
    if (!this.canPlaceSeat(grid, row, col, type)) {
      console.log("Cannot place seat - not enough space");
      return;
    }

    // Generate seat ID (matching API format)
    const seatId = this.generateSeatId(deck, row, col, side);

    // Create seat data
    const position = parseInt(seatPosition.style.top) || row * this.cellHeight;
    const left = parseInt(seatPosition.style.left) || col * this.cellWidth;

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
      is_sleeper: category === "sleeper",
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

    console.log("Seat added:", seatData);
  }

  getSeatPositionAt(grid, x, y) {
    const positions = grid.querySelectorAll(".seat-position");
    console.log(
      "getSeatPositionAt: Looking for position at",
      x,
      y,
      "Found",
      positions.length,
      "positions in grid",
      grid.id,
    );

    for (let pos of positions) {
      const rect = pos.getBoundingClientRect();
      const gridRect = grid.getBoundingClientRect();
      const posX = rect.left - gridRect.left;
      const posY = rect.top - gridRect.top;

      if (
        x >= posX &&
        x <= posX + rect.width &&
        y >= posY &&
        y <= posY + rect.height
      ) {
        console.log(
          "Found matching position:",
          pos.dataset.row,
          pos.dataset.col,
        );
        return pos;
      }
    }
    console.log("No matching position found");
    return null;
  }

  generateSeatId(deck, row, col, side) {
    // Generate seat ID matching API format
    if (deck === "upper_deck") {
      // Upper deck: U1, U2, U3...
      const seatNumber = row * 10 + (col + 1);
      return `U${seatNumber}`;
    } else {
      // Lower deck: 1, 2, 3... (simple numbers)
      const seatNumber = row * 10 + (col + 1);
      return `${seatNumber}`;
    }
  }

  getSeatWidth(type) {
    switch (type) {
      case "nseat":
        return 1; // Seater: 1x1
      case "hseat":
        return 2; // Horizontal sleeper: 2x1
      case "vseat":
        return 1; // Vertical sleeper: 1x2
      default:
        return 1;
    }
  }

  getSeatHeight(type) {
    switch (type) {
      case "nseat":
        return 1; // Seater: 1x1
      case "hseat":
        return 1; // Horizontal sleeper: 2x1
      case "vseat":
        return 2; // Vertical sleeper: 1x2
      default:
        return 1;
    }
  }

  canPlaceSeat(grid, row, col, type) {
    const width = this.getSeatWidth(type);
    const height = this.getSeatHeight(type);

    // Check if seat fits within grid bounds
    if (
      col + width > this.columnsPerRow ||
      row + height > this.getTotalRows()
    ) {
      return false;
    }

    // Check if all required cells are empty
    for (let r = row; r < row + height; r++) {
      for (let c = col; c < col + width; c++) {
        const cell = grid.querySelector(`[data-row="${r}"][data-col="${c}"]`);
        if (!cell || cell.querySelector(".seat-item")) {
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
        if (cell && !(r === row && c === col)) {
          // Skip the main cell
          cell.style.backgroundColor = "#f0f0f0";
          cell.style.pointerEvents = "none";
        }
      }
    }
  }

  getTotalRows() {
    const [leftSeats, rightSeats] = this.seatLayout.split("x").map(Number);
    return leftSeats + rightSeats;
  }

  createSeatElement(deck, seatData, position) {
    const seatElement = document.createElement("div");
    seatElement.className = `seat-item ${seatData.type}`;
    seatElement.style.position = "absolute";
    seatElement.style.left = "0px";
    seatElement.style.top = "0px";
    seatElement.style.width = seatData.width * this.cellWidth + "px";
    seatElement.style.height = seatData.height * this.cellHeight + "px";
    seatElement.style.border = "2px solid #333";
    seatElement.style.borderRadius = "4px";
    seatElement.style.display = "flex";
    seatElement.style.alignItems = "center";
    seatElement.style.justifyContent = "center";
    seatElement.style.fontSize = "12px";
    seatElement.style.fontWeight = "bold";
    seatElement.style.cursor = "pointer";
    seatElement.style.zIndex = "5";
    seatElement.style.transition = "all 0.2s ease";
    seatElement.style.flexDirection = "column";
    seatElement.style.lineHeight = "1.1";
    seatElement.style.padding = "3px";
    seatElement.style.boxSizing = "border-box";

    // Create content with seat ID and price
    const seatIdDiv = document.createElement("div");
    seatIdDiv.textContent = seatData.seat_id;
    seatIdDiv.style.fontSize = "12px";
    seatIdDiv.style.fontWeight = "bold";

    const priceDiv = document.createElement("div");
    priceDiv.textContent = `₹${seatData.price}`;
    priceDiv.style.fontSize = "10px";
    priceDiv.style.opacity = "0.8";

    seatElement.appendChild(seatIdDiv);
    seatElement.appendChild(priceDiv);
    seatElement.dataset.seatId = seatData.seat_id;
    seatElement.dataset.deck = deck;
    seatElement.dataset.seatData = JSON.stringify(seatData);

    // Set seat colors based on type
    switch (seatData.type) {
      case "nseat":
        seatElement.style.backgroundColor = "#fff";
        seatElement.style.color = "#333";
        seatElement.style.borderColor = "#666";
        break;
      case "hseat":
        seatElement.style.backgroundColor = "#e3f2fd";
        seatElement.style.color = "#1976d2";
        seatElement.style.borderColor = "#1976d2";
        break;
      case "vseat":
        seatElement.style.backgroundColor = "#f3e5f5";
        seatElement.style.color = "#7b1fa2";
        seatElement.style.borderColor = "#7b1fa2";
        break;
    }

    // Add hover effect
    seatElement.addEventListener("mouseenter", () => {
      seatElement.style.transform = "scale(1.05)";
      seatElement.style.boxShadow = "0 2px 8px rgba(0, 0, 0, 0.2)";
    });

    seatElement.addEventListener("mouseleave", () => {
      seatElement.style.transform = "scale(1)";
      seatElement.style.boxShadow = "none";
    });

    // Handle seat click for editing
    seatElement.addEventListener("click", (e) => {
      e.stopPropagation();
      this.selectSeat(seatElement);
    });

    // Make seat draggable for repositioning
    seatElement.draggable = true;
    seatElement.addEventListener("dragstart", (e) => {
      e.dataTransfer.setData("text/plain", "reposition");
      e.dataTransfer.effectAllowed = "move";
      this.draggingSeat = seatElement;
      seatElement.style.opacity = "0.5";
    });

    seatElement.addEventListener("dragend", (e) => {
      seatElement.style.opacity = "1";
      this.draggingSeat = null;
    });

    // Clear position content and add seat
    position.innerHTML = "";
    position.appendChild(seatElement);
  }

  selectSeat(seatElement) {
    // Remove previous selection
    document.querySelectorAll(".seat-item.selected").forEach((seat) => {
      seat.classList.remove("selected");
    });

    // Select current seat
    seatElement.classList.add("selected");
    this.selectedSeat = seatElement;

    // Show seat properties panel
    const seatData = JSON.parse(seatElement.dataset.seatData);
    this.showSeatProperties(seatData);
  }

  showSeatProperties(seatData) {
    this.seatIdInput.value = seatData.seat_id;
    this.seatPriceInput.value = seatData.price;
    this.seatTypeSelect.value = seatData.type;
    this.seatPropertiesPanel.style.display = "block";
  }

  hideSeatProperties() {
    this.seatPropertiesPanel.style.display = "none";
    this.selectedSeat = null;
    document.querySelectorAll(".seat-item.selected").forEach((seat) => {
      seat.classList.remove("selected");
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

    this.layoutData[deck].seats.forEach((seat) => {
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

    console.log("Seat updated:", { seatId, price, type });
  }

  deleteSelectedSeat() {
    if (!this.selectedSeat) return;

    if (confirm("Delete this seat?")) {
      const seatId = this.selectedSeat.dataset.seatId;
      const deck = this.selectedSeat.dataset.deck;
      const seatData = JSON.parse(this.selectedSeat.dataset.seatData);

      // Remove from layout data
      this.layoutData[deck].seats = this.layoutData[deck].seats.filter(
        (seat) => seat.seat_id !== seatId,
      );

      // Clear occupied cells
      const grid =
        deck === "upper_deck" ? this.upperDeckGrid : this.lowerDeckGrid;
      this.clearOccupiedCells(grid, seatData.row, seatData.col, seatData.type);

      // Remove visual element and restore position
      const position = this.selectedSeat.parentElement;
      position.innerHTML = "<span>+</span>";

      // Hide properties panel
      this.hideSeatProperties();

      // Update counts
      this.updateSeatCounts();
      this.updateLayoutDataInput();

      console.log("Seat deleted:", seatId);
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
          cell.style.backgroundColor = "rgba(0, 123, 255, 0.1)";
          cell.style.pointerEvents = "auto";
          if (!cell.querySelector(".seat-item")) {
            cell.innerHTML = "<span>+</span>";
          }
        }
      }
    }
  }

  setDeckType(deckType, skipDataClear = false) {
    console.log("=== SETTING DECK TYPE ===");
    console.log(
      "setDeckType called with:",
      deckType,
      "skipDataClear:",
      skipDataClear,
    );
    console.log("Current deck type:", this.deckType);
    console.log("Upper deck grid exists before:", !!this.upperDeckGrid);
    console.log(
      "Upper deck grid children before:",
      this.upperDeckGrid?.children.length,
    );

    // Store existing seat data before recreating grid
    const existingUpperDeckSeats = this.layoutData.upper_deck?.seats || [];
    console.log(
      "Storing existing upper deck seats:",
      existingUpperDeckSeats.length,
    );

    this.deckType = deckType;

    // Clear upper deck data for single decker (but not during initial load)
    if (deckType === "single") {
      if (!skipDataClear) {
        this.layoutData.upper_deck = { seats: [] };
        console.log("Cleared upper deck data for single decker");
      } else {
        console.log("Skipped clearing upper deck data (initial load)");
      }
      // Clear upper deck visual elements
      this.upperDeckGrid.innerHTML = "";
      console.log("Cleared upper deck visual elements for single decker");
    } else {
      // For double decker, only recreate the upper deck layout if not during initial load
      if (!skipDataClear) {
        console.log(
          "Recreating upper deck layout for double decker (user change)",
        );
        console.log(
          "Upper deck grid before createDeckLayout:",
          this.upperDeckGrid,
        );
        this.createDeckLayout(this.upperDeckGrid);
        console.log(
          "Upper deck grid after createDeckLayout:",
          this.upperDeckGrid,
        );
        console.log(
          "Upper deck grid children after createDeckLayout:",
          this.upperDeckGrid?.children.length,
        );

        // Re-render existing seats if we have any
        if (existingUpperDeckSeats.length > 0) {
          console.log(
            "Re-rendering existing upper deck seats after grid recreation",
          );
          existingUpperDeckSeats.forEach((seat, index) => {
            console.log(`Re-rendering upper deck seat ${index}:`, seat);
            const grid = this.upperDeckGrid;
            const selector = `[data-row="${seat.row}"][data-col="${seat.col}"][data-side="${seat.side}"]`;
            const position = grid.querySelector(selector);
            if (position) {
              console.log(
                `Re-creating upper deck seat element for seat ${index}`,
              );
              this.createSeatElement("upper_deck", seat, position);
            } else {
              console.error(
                `Position not found for re-rendering upper deck seat ${index}:`,
                seat,
              );
            }
          });
        }
      } else {
        console.log(
          "Skipping upper deck grid recreation during initial load (seats already loaded)",
        );
      }
    }

    console.log("Upper deck grid exists after:", !!this.upperDeckGrid);
    console.log(
      "Upper deck grid children after:",
      this.upperDeckGrid?.children.length,
    );

    // Apply deck type settings to UI
    if (!this.isInitializing) {
      this.applyDeckTypeSettings();
    }

    console.log("=== DECK TYPE SET COMPLETE ===");

    this.updateSeatCounts();
    this.updateLayoutDataInput();
  }

  setSeatLayout(layout) {
    this.seatLayout = layout;

    // Recreate bus layout
    this.createBusLayout();

    // Clear existing seats
    this.clearAllSeats();

    console.log("Seat layout changed to:", layout);
  }

  setColumnsPerRow(columns) {
    this.columnsPerRow = columns;

    // Recreate bus layout
    this.createBusLayout();

    // Clear existing seats
    this.clearAllSeats();

    console.log("Columns per row changed to:", columns);
  }

  clearAllSeats() {
    // Clear layout data
    this.layoutData = {
      upper_deck: { seats: [] },
      lower_deck: { seats: [] },
    };

    // Clear visual elements but keep positions
    this.upperDeckGrid.querySelectorAll(".seat-item").forEach((seat) => {
      const position = seat.parentElement;
      position.innerHTML = "<span>+</span>";
    });
    this.lowerDeckGrid.querySelectorAll(".seat-item").forEach((seat) => {
      const position = seat.parentElement;
      position.innerHTML = "<span>+</span>";
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
    if (this.deckType === "double") {
      upperCount = this.layoutData.upper_deck.seats
        ? this.layoutData.upper_deck.seats.length
        : 0;
    }

    // Count lower deck seats
    lowerCount = this.layoutData.lower_deck.seats
      ? this.layoutData.lower_deck.seats.length
      : 0;

    this.upperDeckSeatsInput.value = upperCount;
    this.lowerDeckSeatsInput.value = lowerCount;
    this.totalSeatsInput.value = upperCount + lowerCount;
  }

  updateLayoutDataInput() {
    // Include configuration in the layout data
    const dataWithConfig = {
      ...this.layoutData,
      configuration: {
        seatLayout: this.seatLayout,
        deckType: this.deckType,
        columnsPerRow: this.columnsPerRow,
      },
    };
    this.layoutDataInput.value = JSON.stringify(dataWithConfig);
  }

  clearLayout() {
    if (confirm("Clear the entire layout? This action cannot be undone.")) {
      this.clearAllSeats();
    }
  }

  async showPreview() {
    try {
      // Get CSRF token from meta tag or form
      const csrfToken =
        document
          .querySelector('meta[name="csrf-token"]')
          ?.getAttribute("content") ||
        document.querySelector('input[name="_token"]')?.value ||
        "";

      console.log("Sending preview request to:", this.previewUrl);
      console.log("Layout data:", this.layoutData);
      console.log("Upper deck seats:", this.layoutData.upper_deck.seats);
      console.log("Lower deck seats:", this.layoutData.lower_deck.seats);

      const response = await fetch(this.previewUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrfToken,
          Accept: "application/json",
        },
        body: JSON.stringify({
          layout_data: JSON.stringify(this.layoutData),
        }),
      });

      console.log("Response status:", response.status);
      console.log("Response headers:", response.headers);

      // Check if response is ok
      if (!response.ok) {
        const errorText = await response.text();
        console.error("Server error response:", errorText);
        throw new Error(
          `Server error: ${response.status} - ${response.statusText}`,
        );
      }

      // Check if response is JSON
      const contentType = response.headers.get("content-type");
      if (!contentType || !contentType.includes("application/json")) {
        const responseText = await response.text();
        console.error("Non-JSON response:", responseText);
        throw new Error(
          "Server returned non-JSON response. Check server logs.",
        );
      }

      const result = await response.json();
      console.log("Preview result:", result);

      if (result.success) {
        this.previewContent.innerHTML = `
                    <style>
                        .preview-seat-item {
                            position: absolute;
                            border: 2px solid;
                            border-radius: 6px;
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                            font-size: 12px;
                            font-weight: bold;
                            cursor: pointer;
                            line-height: 1.1;
                            padding: 3px;
                            box-sizing: border-box;
                        }
                        .preview-seat-item.nseat {
                            width: 45px !important;
                            height: 40px !important;
                            background-color: #fff;
                            border-color: #666;
                            color: #333;
                        }
                        .preview-seat-item.hseat {
                            width: 60px !important;
                            height: 40px !important;
                            background-color: #e3f2fd;
                            border-color: #1976d2;
                            color: #1976d2;
                        }
                        .preview-seat-item.vseat {
                            width: 40px !important;
                            height: 80px !important;
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
                                ${result.html_layout
            .replace(
              /class="nseat"/g,
              'class="preview-seat-item nseat"',
            )
            .replace(
              /class="hseat"/g,
              'class="preview-seat-item hseat"',
            )
            .replace(
              /class="vseat"/g,
              'class="preview-seat-item vseat"',
            )}
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
        alert("Error generating preview: " + (result.error || "Unknown error"));
      }
    } catch (error) {
      console.error("Preview error:", error);
      alert("Error generating preview: " + error.message);
    }
  }

  getLayoutData() {
    return this.layoutData;
  }

  applyDeckTypeSettings() {
    console.log("=== APPLYING DECK TYPE SETTINGS ===");
    console.log("Current deck type:", this.deckType);

    if (this.deckType === "single") {
      // Hide upper deck section
      if (this.upperDeckSection) {
        this.upperDeckSection.style.display = "none";
      }
      // Show only lower deck (which acts as the main deck in single mode)
      if (this.lowerDeckLabel) {
        this.lowerDeckLabel.textContent = "Bus Layout";
      }
    } else if (this.deckType === "double") {
      // Show upper deck section
      if (this.upperDeckSection) {
        this.upperDeckSection.style.display = "block";
      }
      // Update lower deck label
      if (this.lowerDeckLabel) {
        this.lowerDeckLabel.textContent = "Lower Deck";
      }
    }

    console.log("Deck type settings applied");
  }

  loadExistingConfiguration() {
    this.isInitializing = true;
    const existingData = this.layoutDataInput.value;
    console.log("=== LOADING EXISTING CONFIGURATION ===");
    console.log("Raw existing data:", existingData);

    if (existingData && existingData !== "{}") {
      try {
        const layoutData = JSON.parse(existingData);
        console.log("Parsed layout data for configuration:", layoutData);

        // Extract configuration from existing data
        if (layoutData.configuration) {
          this.seatLayout = layoutData.configuration.seatLayout || "2x1";
          this.deckType = layoutData.configuration.deckType || "single";
          this.columnsPerRow = layoutData.configuration.columnsPerRow || 10;

          console.log("Loaded configuration:", {
            seatLayout: this.seatLayout,
            deckType: this.deckType,
            columnsPerRow: this.columnsPerRow,
          });

          // Update UI elements to match loaded configuration
          if (this.seatLayoutSelect) {
            this.seatLayoutSelect.value = this.seatLayout;
          }
          if (this.deckTypeSelect) {
            this.deckTypeSelect.value = this.deckType;
          }
          if (this.columnsPerRowInput) {
            this.columnsPerRowInput.value = this.columnsPerRow;
          }
        } else {
          // Try to infer configuration from existing seats (fallback)
          const hasUpperSeats = layoutData.upper_deck?.seats?.length > 0;
          const hasLowerSeats = layoutData.lower_deck?.seats?.length > 0;

          if (hasLowerSeats) {
            this.deckType = "double";
            if (this.deckTypeSelect) {
              this.deckTypeSelect.value = "double";
            }
          }

          // Infer seat layout from maximum row number in existing seats
          let maxRow = -1;
          if (layoutData.lower_deck?.seats && layoutData.lower_deck.seats.length > 0) {
            console.log("Checking lower deck seats for max row. First seat:", layoutData.lower_deck.seats[0]);
            layoutData.lower_deck.seats.forEach((seat, index) => {
              const rowNum = seat.row !== undefined && seat.row !== null ? parseInt(seat.row) : -1;
              if (!isNaN(rowNum) && rowNum >= 0 && rowNum > maxRow) {
                maxRow = rowNum;
                console.log(`Found new maxRow: ${maxRow} from seat ${index} (seat_id: ${seat.seat_id})`);
              }
            });
          }
          if (layoutData.upper_deck?.seats && layoutData.upper_deck.seats.length > 0) {
            console.log("Checking upper deck seats for max row. First seat:", layoutData.upper_deck.seats[0]);
            layoutData.upper_deck.seats.forEach((seat, index) => {
              const rowNum = seat.row !== undefined && seat.row !== null ? parseInt(seat.row) : -1;
              if (!isNaN(rowNum) && rowNum >= 0 && rowNum > maxRow) {
                maxRow = rowNum;
                console.log(`Found new maxRow: ${maxRow} from upper deck seat ${index} (seat_id: ${seat.seat_id})`);
              }
            });
          }

          console.log("🔍 Inferring seat layout from existing seats:", {
            maxRow,
            lowerDeckSeats: layoutData.lower_deck?.seats?.length || 0,
            upperDeckSeats: layoutData.upper_deck?.seats?.length || 0,
            sampleSeat: layoutData.lower_deck?.seats?.[0],
            lastSeat: layoutData.lower_deck?.seats?.[layoutData.lower_deck.seats.length - 1]
          });

          // Calculate seat layout based on max row (rows are 0-indexed, so maxRow+1 = total rows)
          // For 2x2: rows 0,1 above aisle (2 rows), aisle, rows 2,3 below aisle (2 rows) = 4 total rows
          // For 2x3: rows 0,1 above aisle (2 rows), aisle, rows 2,3,4 below aisle (3 rows) = 5 total rows
          if (maxRow >= 0) {
            const totalRows = maxRow + 1;
            console.log("Calculating layout for", totalRows, "total rows (maxRow:", maxRow + ")");

            // Try to infer layout: if totalRows is 4, likely 2x2; if 5, likely 2x3; if 3, likely 2x1
            if (totalRows === 4) {
              this.seatLayout = "2x2";
            } else if (totalRows === 5) {
              this.seatLayout = "2x3";
            } else if (totalRows === 3) {
              this.seatLayout = "2x1";
            } else {
              // Default: try to split rows evenly
              const leftRows = Math.ceil(totalRows / 2);
              const rightRows = totalRows - leftRows;
              this.seatLayout = `${leftRows}x${rightRows}`;
            }

            if (this.seatLayoutSelect) {
              this.seatLayoutSelect.value = this.seatLayout;
            }

            console.log("✅ Inferred seat layout from max row:", {
              maxRow,
              totalRows,
              seatLayout: this.seatLayout,
              willCreateRows: totalRows
            });
          } else {
            console.log("⚠️ No seats found or maxRow is -1, using default layout 2x1");
          }

          console.log("Inferred configuration from seats:", {
            deckType: this.deckType,
            hasUpperSeats,
            hasLowerSeats,
            maxRow,
            seatLayout: this.seatLayout
          });
        }
      } catch (error) {
        console.error("Error loading existing configuration:", error);
      }
    } else {
      console.log("No existing configuration found, using defaults");
    }

    this.isInitializing = false;
  }

  loadExistingData() {
    const existingData = this.layoutDataInput.value;
    console.log("=== LOADING EXISTING SEAT DATA ===");
    console.log("Raw existing data:", existingData);

    if (existingData && existingData !== "{}") {
      try {
        this.layoutData = JSON.parse(existingData);
        console.log("Parsed layout data:", this.layoutData);
        console.log("Upper deck data:", this.layoutData.upper_deck);
        console.log("Lower deck data:", this.layoutData.lower_deck);
        console.log(
          "Upper deck seats count:",
          this.layoutData.upper_deck?.seats?.length || 0,
        );
        console.log(
          "Lower deck seats count:",
          this.layoutData.lower_deck?.seats?.length || 0,
        );

        this.renderExistingLayout();
      } catch (error) {
        console.error("Error loading existing layout data:", error);
      }
    } else {
      console.log("No existing seat data found or empty data");
      // Initialize empty layout data structure
      this.layoutData = {
        upper_deck: { seats: [] },
        lower_deck: { seats: [] },
      };
    }
  }

  renderExistingLayout() {
    console.log("=== RENDERING EXISTING LAYOUT ===");
    console.log("Upper deck grid exists:", !!this.upperDeckGrid);
    console.log("Lower deck grid exists:", !!this.lowerDeckGrid);
    console.log(
      "Upper deck grid children before clear:",
      this.upperDeckGrid?.children.length,
    );
    console.log(
      "Lower deck grid children before clear:",
      this.lowerDeckGrid?.children.length,
    );

    // Check if we have enough rows for all seats
    let maxLowerRow = -1;
    let maxUpperRow = -1;

    if (this.layoutData.lower_deck?.seats) {
      this.layoutData.lower_deck.seats.forEach(seat => {
        if (seat.row !== undefined && seat.row > maxLowerRow) {
          maxLowerRow = seat.row;
        }
      });
    }

    if (this.layoutData.upper_deck?.seats) {
      this.layoutData.upper_deck.seats.forEach(seat => {
        if (seat.row !== undefined && seat.row > maxUpperRow) {
          maxUpperRow = seat.row;
        }
      });
    }

    console.log("Max rows found in seats:", {
      maxLowerRow,
      maxUpperRow,
      currentSeatLayout: this.seatLayout
    });

    // If we don't have enough rows, recreate the layout with correct configuration
    const [leftSeats, rightSeats] = this.seatLayout.split("x").map(Number);
    const totalRows = leftSeats + rightSeats;
    const maxRowNeeded = Math.max(maxLowerRow, maxUpperRow, -1) + 1; // +1 because rows are 0-indexed

    console.log("Row check:", {
      totalRows,
      maxRowNeeded,
      needsRecreation: maxRowNeeded > totalRows
    });

    if (maxRowNeeded > totalRows && maxRowNeeded > 0) {
      console.warn(`Not enough rows! Need ${maxRowNeeded} but have ${totalRows}. Recreating layout...`);
      // Calculate new layout - try to maintain 2x2 or 2x3 pattern
      let newLeftRows, newRightRows;
      if (maxRowNeeded === 4) {
        newLeftRows = 2;
        newRightRows = 2;
        this.seatLayout = "2x2";
      } else if (maxRowNeeded === 5) {
        newLeftRows = 2;
        newRightRows = 3;
        this.seatLayout = "2x3";
      } else {
        // Default: split rows evenly
        newLeftRows = Math.ceil(maxRowNeeded / 2);
        newRightRows = maxRowNeeded - newLeftRows;
        this.seatLayout = `${newLeftRows}x${newRightRows}`;
      }

      if (this.seatLayoutSelect) {
        this.seatLayoutSelect.value = this.seatLayout;
      }

      console.log("Recreating layout with:", {
        newSeatLayout: this.seatLayout,
        totalRows: maxRowNeeded,
        newLeftRows,
        newRightRows
      });

      // Recreate the bus layout with correct number of rows
      this.createBusLayout();

      console.log("Layout recreated. Grid should now have", maxRowNeeded, "rows");
    }

    // Clear existing seats but keep positions
    this.upperDeckGrid.querySelectorAll(".seat-item").forEach((seat) => {
      const position = seat.parentElement;
      position.innerHTML = "<span>+</span>";
    });
    this.lowerDeckGrid.querySelectorAll(".seat-item").forEach((seat) => {
      const position = seat.parentElement;
      position.innerHTML = "<span>+</span>";
    });

    console.log(
      "Upper deck grid children after clear:",
      this.upperDeckGrid?.children.length,
    );
    console.log(
      "Lower deck grid children after clear:",
      this.lowerDeckGrid?.children.length,
    );

    // Render upper deck seats
    if (this.layoutData.upper_deck && this.layoutData.upper_deck.seats) {
      console.log("=== RENDERING UPPER DECK SEATS ===");
      console.log(
        "Upper deck seats to render:",
        this.layoutData.upper_deck.seats.length,
      );

      this.layoutData.upper_deck.seats.forEach((seat, index) => {
        console.log(`Upper deck seat ${index}:`, seat);
        const grid = this.upperDeckGrid;
        const selector = `[data-row="${seat.row}"][data-col="${seat.col}"][data-side="${seat.side}"]`;
        console.log(`Looking for position with selector: ${selector}`);

        const position = grid.querySelector(selector);
        console.log(
          `Found position for upper deck seat ${index}:`,
          !!position,
          position,
        );

        if (position) {
          console.log(`Creating upper deck seat element for seat ${index}`);
          this.createSeatElement("upper_deck", seat, position);
        } else {
          console.error(
            `Position not found for upper deck seat ${index}:`,
            seat,
          );
          console.log("Available positions in upper deck grid:");
          const allPositions = grid.querySelectorAll(".seat-position");
          allPositions.forEach((pos, i) => {
            console.log(`Position ${i}:`, {
              row: pos.dataset.row,
              col: pos.dataset.col,
              side: pos.dataset.side,
            });
          });
        }
      });
    } else {
      console.log("No upper deck seats to render");
    }

    // Render lower deck seats
    if (this.layoutData.lower_deck && this.layoutData.lower_deck.seats) {
      console.log("=== RENDERING LOWER DECK SEATS ===");
      console.log(
        "Lower deck seats to render:",
        this.layoutData.lower_deck.seats.length,
      );

      this.layoutData.lower_deck.seats.forEach((seat, index) => {
        console.log(`Lower deck seat ${index}:`, seat);
        const grid = this.lowerDeckGrid;
        const selector = `[data-row="${seat.row}"][data-col="${seat.col}"][data-side="${seat.side}"]`;
        console.log(`Looking for position with selector: ${selector}`);

        const position = grid.querySelector(selector);
        console.log(
          `Found position for lower deck seat ${index}:`,
          !!position,
          position,
        );

        if (position) {
          console.log(`Creating lower deck seat element for seat ${index}`);
          this.createSeatElement("lower_deck", seat, position);
        } else {
          console.error(
            `Position not found for lower deck seat ${index}:`,
            seat,
          );
        }
      });
    } else {
      console.log("No lower deck seats to render");
    }

    this.updateSeatCounts();
    console.log("=== RENDERING COMPLETE ===");
  }
}
