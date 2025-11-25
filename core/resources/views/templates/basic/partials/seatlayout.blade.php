{{-- Log to the console the seat HTML for debugging purposes $seatHtml  --}}

<div class="bus">{!! renderSeatHTML($seatHtml, $parsedLayout ?? null, $isOperatorBus ?? false) !!}</div>

@push('style')
    <style>
        .bus {
            max-width: 460px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            height: auto;
            align-items: stretch;
        }

        .outerseat,
        .outerlowerseat {
            display: flex;
            padding: 12px 8px;
            position: relative;
            background: linear-gradient(145deg, #ebebeb, #e0e0e0);
            border: 1px solid #d5d5d5;
        }

        /* Prevent seat overflow */
        .busSeat {
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 10px;
        }

        .busSeatlft {
            width: 12%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            /* flex-shrink: 0; */
        }

        .upper,
        .lower {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            letter-spacing: 0.5px;
            transform: rotate(180deg);
        }

        .upper::before {
            content: "Upper";
        }

        .lower::before {
            content: "Lower";
        }

        /* Enhanced steering wheel for lower deck */
        .outerlowerseat .busSeatlft::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            width: 32px;
            height: 32px;
            background-color: #555;
            border-radius: 50%;
            border: 1px solid #777;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .outerlowerseat .busSeatlft::after {
            content: '';
            position: absolute;
            top: 21px;
            left: 50%;
            transform: translateX(-50%);
            width: 18px;
            height: 18px;
            border: 2px solid #fff;
            border-radius: 50%;
            background: radial-gradient(circle, #fff 30%, transparent 30%);
        }

        /* Make busSeatrgt a flexible grid to align rows and columns */
        .busSeatrgt {
            flex: 1;
            margin-left: 1rem;
            display: grid;
            grid-auto-rows: min-content;
            justify-items: end;
            gap: 8px;
        }

        /* Adjust seatcontainer to be flexible */
        .seatcontainer {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
            height: auto;
        }

        .seat-row {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
        }

        /* Seater Seats */
        .nseat {
            width: 24px;
            height: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Normal Sleeper Seats */
        .hseat {
            width: 56px;
            height: 22px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Vertical Sleeper Seats */
        .vseat {
            width: 22px;
            height: 56px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Booked Seater Seats */
        .bseat {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* Booked Normal Sleeper Seats */
        .bhseat {
            width: 56px;
            height: 22px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /*Booked Vertical Sleeper Seats */
        .bvseat {
            width: 22px;
            height: 56px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* Hover effect on available seats */
        .nseat:hover,
        .vseat:hover,
        .hseat:hover {
            border-color: #4CAF50;
            background-color: #e8f5e9;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Seat labels/numbers */
        .nseat::after,
        .hseat::after,
        .vseat::after {
            content: attr(data-seat);
            padding: 6px;
            font-size: 8px;
            font-weight: 600;
            color: #666;
            pointer-events: none;
        }

        .nseat.selected,
        .hseat.selected,
        .vseat.selected {
            background-color: #c8e6c9;
            border-color: #81c784;
            box-shadow: 0 2px 6px rgba(233, 30, 99, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .hseat.selected::after,
        .vseat.selected::after {
            color: #fff;
        }

        .clr {
            clear: both;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Target all dynamically generated rows like .row1, .row2, etc. */
        [class^="row"] {
            display: flex;
            justify-content: flex-end;
            align-self: flex-end;
            gap: 6px;
            margin-bottom: 8px;
            position: relative;
        }

        [class^="row"].aisle {
            justify-content: space-between;
        }
    </style>
@endpush

@push('script')
    <script>
        // Log seatHtml to console for debugging
        console.log('Seat HTML Data:', {!! json_encode($seatHtml) !!});

        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                const seatDivs = document.querySelectorAll('.seatcontainer > div');
                const seatData = [];

                seatDivs.forEach(seat => {
                    const style = seat.getAttribute('style') || '';
                    const topMatch = /top\s*:\s*(\d+)px/i.exec(style);
                    const leftMatch = /left\s*:\s*(\d+)px/i.exec(style);

                    // If seats don't have inline positioning (clean HTML), skip the reorganization
                    if (!topMatch || !leftMatch) {
                        return;
                    }

                    const top = parseInt(topMatch[1], 10);
                    const left = parseInt(leftMatch[1], 10);

                    seatData.push({
                        element: seat,
                        top,
                        left
                    });
                });

                // If no seats with positioning found, don't reorganize (clean HTML case)
                if (seatData.length === 0) {
                    return;
                }

                const rowsMap = {};
                seatData.forEach(({
                    top,
                    left,
                    element
                }) => {
                    if (!rowsMap[top]) rowsMap[top] = [];
                    rowsMap[top].push({
                        left,
                        element
                    });
                });

                const sortedTops = Object.keys(rowsMap).map(n => parseInt(n)).sort((a, b) => a - b);
                const result = {};
                let rowCounter = 1;

                for (let i = 0; i < sortedTops.length; i++) {
                    const top = sortedTops[i];
                    const seats = rowsMap[top];
                    seats.sort((a, b) => a.left - b.left);

                    const prevTop = sortedTops[i - 1];
                    const nextTop = sortedTops[i + 1];
                    const isAisle = (!prevTop && nextTop && nextTop - top > 30) ||
                        (!nextTop && prevTop && top - prevTop > 30) ||
                        (prevTop && nextTop && nextTop - top > 30 && top - prevTop > 30) ||
                        seats.length <= 1;

                    const rowName = isAisle ? 'aisle' : `row${rowCounter++}`;
                    result[rowName] = [];

                    for (const {
                            left,
                            element
                        }
                        of seats) {
                        result[rowName].push({
                            top,
                            left
                        });
                        element.classList.add(rowName);
                        element.removeAttribute('style');

                        // ---- ENHANCE SEAT CONTENT ----
                        const onclick = element.getAttribute('onclick') || '';
                        const match = onclick.match(
                            /AddRemoveSeat\(this,\s*['"]([^'"]+)['"]\s*,\s*['"]([^'"]+)['"]\)/);
                        if (!match) continue;

                        const seatId = match[1];
                        const price = match[2];

                        element.setAttribute('data-seat', seatId);
                        element.setAttribute('data-price', price);
                    }
                }

                const seatContainers = document.querySelectorAll('.seatcontainer');
                seatContainers.forEach(deck => {
                    const newContainer = document.createDocumentFragment();
                    Object.entries(result).forEach(([rowName, seats]) => {
                        const rowDiv = document.createElement('div');
                        rowDiv.className = `seat-row ${rowName}`;
                        rowDiv.style.display = 'flex';
                        rowDiv.style.justifyContent = 'flex-end';
                        rowDiv.style.gap = '6px';
                        rowDiv.style.marginBottom = '8px';

                        seats.sort((a, b) => a.left - b.left).forEach(({
                            left
                        }) => {
                            const seat = [...deck.children].find(el => el.classList
                                .contains(rowName) && !rowDiv
                                .contains(el));
                            if (seat) rowDiv.appendChild(seat);
                        });

                        newContainer.appendChild(rowDiv);
                    });

                    deck.innerHTML = '';
                    deck.appendChild(newContainer);
                });

            }, 0);
        });
    </script>
@endpush
