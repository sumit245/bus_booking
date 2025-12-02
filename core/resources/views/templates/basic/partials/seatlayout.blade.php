{{-- Log to the console the seat HTML for debugging purposes $seatHtml  --}}

<div class="bus-wrapper-mobile">
    <div class="bus">{!! renderSeatHTML($seatHtml, $parsedLayout ?? null, $isOperatorBus ?? false) !!}</div>
</div>

@push('style')
    <style>
        .bus-wrapper-mobile {
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            display: block;
            -webkit-overflow-scrolling: touch;
        }

        .bus-wrapper-mobile::-webkit-scrollbar {
            display: none;
        }

        .bus {
            max-width: 460px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            height: auto;
            align-items: stretch;
        }

        /* Mobile: Rotate bus layout vertically (90 degrees clockwise) */
        @media (max-width: 991px) {

            /* Override parent padding on mobile */
            .seat-overview-wrapper .bus-wrapper-mobile {
                margin: 0px -20px 0 -20px !important;
            }

            .bus-wrapper-mobile {
                height: 60vh;
                width: 100%;
                margin: 0;
                padding: 0 !important;
                position: relative;
                left: 5%;
                overflow-x: scroll;
                overflow-y: hidden;
                -webkit-overflow-scrolling: touch;
            }

            .bus {
                position: absolute;
                top: 0;
                left: 50%;
                transform: translateX(-50%) rotate(90deg);
                transform-origin: center center;
                width: calc(100vh - 10px);
                height: auto;
                margin: 0;
                gap: 8px;
                display: flex;
                flex-direction: column;
            }

            /* Preserve structure - outerseat/outerlowerseat maintain relative positioning and start from top */
            .bus .outerseat,
            .bus .outerlowerseat,
            .bus .outerupperseat {
                position: relative !important;
                display: flex !important;
                margin: 0 !important;
                padding: 8px !important;
                box-sizing: border-box;
                overflow: hidden !important;
            }

            /* Preserve busSeatlft width - steering and driver cabin */
            .bus .busSeatlft {
                width: 12% !important;
                min-width: 50px !important;
                max-width: 60px !important;
                flex-shrink: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                position: relative !important;
                visibility: visible !important;
                opacity: 1 !important;
                height: 100% !important;
                box-sizing: border-box;
            }

            /* Ensure busSeatrgt alignment and containment */
            .bus .busSeatrgt {
                flex: 1 !important;
                margin-left: 1rem !important;
                display: grid !important;
                grid-auto-rows: min-content !important;
                justify-items: center !important;
                gap: 6px !important;
                width: auto !important;
                max-width: 100% !important;
                overflow: visible !important;
                box-sizing: border-box;
            }

            /* Ensure seatcontainer alignment and containment */
            .bus .seatcontainer {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                overflow: visible !important;
                box-sizing: border-box;
            }

            /* Center seat rows */
            .bus .seat-row,
            .bus [class^="row"] {
                display: flex !important;
                justify-content: center !important;
                gap: 6px !important;
                margin-bottom: 6px !important;
                width: 100% !important;
            }

            /* Prevent busSeat overflow - keep it contained */
            .bus .busSeat {
                overflow: visible !important;
                height: auto !important;
                box-sizing: border-box;
            }

            /* Make seats touch-friendly */
            .bus .nseat {
                width: 32px !important;
                height: 32px !important;
                font-size: 11px !important;
            }

            .bus .hseat {
                width: 65px !important;
                height: 30px !important;
                font-size: 11px !important;
            }

            .bus .vseat {
                width: 30px !important;
                height: 65px !important;
                font-size: 11px !important;
            }
        }

        @media (max-width: 575px) {
            .bus-wrapper-mobile {
                margin: 0;
                padding: 0 !important;
            }

            .bus {
                top: 0;
                gap: 6px;
            }

            .bus .nseat {
                width: 30px !important;
                height: 30px !important;
                font-size: 10px !important;
            }

            .bus .hseat {
                width: 60px !important;
                height: 28px !important;
                font-size: 10px !important;
            }

            .bus .vseat {
                width: 28px !important;
                height: 60px !important;
                font-size: 10px !important;
            }
        }

        .outerseat,
        .outerlowerseat {
            display: flex;
            position: relative;
            background: linear-gradient(145deg, #ebebeb, #e0e0e0);
            border: 1px solid #d5d5d5;
            border-radius: 6px;
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
